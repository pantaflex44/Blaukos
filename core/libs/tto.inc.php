<?php

/**
 * Blaukos - PHP Micro Framework
 * 
 * MIT License
 * 
 * Copyright (C) 2021 Christophe LEMOINE
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Core\Libs;

use Core\Engine;
use DateTimeImmutable;
use Exception;
use IntlTimeZone;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use ReflectionClass;

/**
 * Tto - Table To Object
 * Micro ORM to manage sql tables with auto-incremented `id` field.
 * Support PHP DocBook annotations for extended objects.
 */
class Tto
{

    private Engine $_engine;
    private array $_sqlTypes = [];
    private array $_sqlParamTypes = [];
    private array $_annotationsRegexes = [];

    private string $_tableName = '';
    private array $_fields = [];
    private array $_defaults = [];

    private array $_query = [];
    private int $_offset = -1;
    private int $_limit = -1;
    private array $_order = [];
    private array $_assoc = [];
    private array $_updated = [];

    /**
     * Initialize parameters
     *
     * @return void
     */
    private function _initialize(): void
    {
        $this->_sqlTypes = [
            'integer'       => 'INT',
            'float'         => 'FLOAT',
            'string'        => 'VARCHAR(255)',
            'boolean'       => 'TINYINT(1)',
            'datetime'      => 'DATETIME',
            'date'          => 'DATE',
            'time'          => 'TIME',
        ];

        $this->_sqlParamTypes = [
            'integer'       => PDO::PARAM_INT,
            'float'         => PDO::PARAM_INT,
            'string'        => PDO::PARAM_STR,
            'boolean'       => PDO::PARAM_INT,
            'datetime'      => PDO::PARAM_STR,
            'date'          => PDO::PARAM_STR,
            'time'          => PDO::PARAM_STR,
        ];

        $this->_annotationsRegexes = [
            'table'         => '/@table[\s\t]+(\w+)/',
            'field'         => '/@field[\s\t]+(\w+):(' . implode('|', array_keys($this->_sqlTypes)) . ')[\s\t]+"(.*)"/',
        ];
    }

    /**
     * Test if type exists
     *
     * @param string $type Type to test
     * @return string|null Sanitized type or null if not exists
     */
    private function _typeExists(string $type): ?string
    {
        $type = strtolower(trim($type));

        if (array_key_exists($type, $this->_sqlTypes)) {
            return $type;
        }

        return null;
    }

    /**
     * Convert a value to an allowed type
     *
     * @param $value Value to convert
     * @param string $type Wanted type from allowed type
     * @return The value converted
     */
    private function _convert($value, string $type)
    {
        $type = $this->_typeExists($type);
        if (is_null($type)) {
            return strval($value);
        }

        try {
            switch ($type) {
                case 'integer':
                    return intval($value);
                case 'float':
                    return floatval($value);
                case 'string':
                    return strval($value);
                case 'boolean':
                    return (intval($value) == 1) ? true : false;
                case 'datetime':
                    return (new DateTimeImmutable())->createFromFormat('Y-m-d H:i:s', $value, (IntlTimeZone::createDefault())->toDateTimeZone());
                case 'date':
                    return (new DateTimeImmutable())->createFromFormat('Y-m-d', $value, (IntlTimeZone::createDefault())->toDateTimeZone());
                case 'time':
                    return (new DateTimeImmutable())->createFromFormat('H:i:s', $value, (IntlTimeZone::createDefault())->toDateTimeZone());
                default:
                    return strval($value);
            }
        } catch (Exception $ex) {
            return strval($value);
        }
    }

    /**
     * Convert a value from an allowed type to sql type
     *
     * @param $value Value to convert
     * @param string $type Wanted type from allowed type to sql type
     * @return The value converted
     */
    private function _toSqlType($sqlvalue, string $type)
    {
        $type = $this->_typeExists($type);
        if (is_null($type)) {
            return strval($sqlvalue);
        }

        try {
            switch ($type) {
                case 'integer':
                    return intval($sqlvalue);
                case 'float':
                    return floatval($sqlvalue);
                case 'string':
                    return strval($sqlvalue);
                case 'boolean':
                    return $sqlvalue === true ? 1 : 0;
                case 'datetime':
                    return $sqlvalue->format('Y-m-d H:i:s');
                case 'date':
                    return $sqlvalue->format('Y-m-d');
                case 'time':
                    return $sqlvalue->format('H:i:s');
                default:
                    return strval($sqlvalue);
            }
        } catch (Exception $ex) {
            return strval($sqlvalue);
        }
    }

    /**
     * Return a field sql converted value by his name
     *
     * @param string $name Field name
     * @return void
     */
    public function _toSqlValue(string $name)
    {
        if (array_key_exists($name, $this->_fields)) {
            $v = $this->_fields[$name]['value'];
            $t = $this->_fields[$name]['type'];

            $sv = $this->_toSqlType($v, $t);
            return $sv;
        }

        throw (new InvalidArgumentException("Bad property name: $name"));
        return null;
    }

    /**
     * Read annotations to define table
     *
     * @return boolean true, everything is good, else, false on error
     */
    private function _annotationsReader(): bool
    {
        $rc = new ReflectionClass($this);

        $comments = $rc->getDocComment();
        if ($comments === false) {
            return false;
        }

        // get the table name
        $found = preg_match($this->_annotationsRegexes['table'], $comments, $matches);
        if (!$found || !is_array($matches) || count($matches) != 2 || trim($matches[1]) == '') {
            return false;
        }

        $this->_tableName = trim($matches[1]);

        // get fields
        $this->_updated = [];
        $this->_fields = [];
        $this->_defaults = [];

        $found = preg_match_all($this->_annotationsRegexes['field'], $comments, $matches);
        if (!$found || !is_array($matches) || count($matches) != 4 || !is_array($matches[0])) {
            return false;
        }

        for ($i = 0; $i < count((array)$matches[0]); $i++) {
            $type = $this->_typeExists($matches[2][$i]);
            if (is_null($type)) {
                continue;
            }

            $key = trim($matches[1][$i]);
            $default = $this->_convert(trim($matches[3][$i]), $type);

            $this->_fields[$key] = ['type' => $type, 'value' => $default];
            $this->_defaults[$key] = $this->_fields[$key];
        }

        if (count($this->_fields) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Return the SQL param type from a field name
     *
     * @param string $fieldName The field name
     * @return integer The PDO Param type
     */
    private function _fieldToSqlParamType(string $name): int
    {
        if (!array_key_exists($name, $this->_fields)) {
            return PDO::PARAM_STR;
        }

        return $this->_sqlParamTypes[$this->_fields[$name]['type']];
    }

    /**
     * Prepare get action
     *
     * @return PDOStatement|null
     */
    private function _toGet(): ?PDOStatement
    {
        try {
            $conn = $this->engine()->db()->connection();

            $query = $this->_query['query'];

            if (count($this->_order) == 2) {
                $query .= ' ORDER BY ' . $this->_order[0] . ' ' . $this->_order[1];
            }

            if ($this->_limit != -1) {
                $query .= ' LIMIT ' . $this->_limit;
            }

            if ($this->_offset != -1) {
                $query .= ' OFFSET ' . $this->_offset;
            }

            $stmt = $conn->prepare($query);
            foreach ($this->_query['params'] as $key => $value) {
                $stmt->bindParam(':' . $key, $value, $this->_fieldToSqlParamType($key));
            }

            if (!$stmt->execute()) {
                return null;
            }

            return $stmt;
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Prepare where command
     *
     * @param string $name Name of field to compare
     * @param string $operator Operator (eg: = | != | < | > | <= | >= )
     * @return array|null Array of prepared datas or null if error
     */
    private function _prepareWhere(string $name, string $operator): ?array
    {
        $name = trim($name);
        if (!array_key_exists($name, $this->_fields)) {
            return null;
        }

        $operator = trim($operator);
        if (!in_array($operator, ['=', '!=', '<', '>', '<=', '>='])) {
            return null;
        }

        return [$name, $operator];
    }

    /**
     * Allow to share the engine with models
     *
     * @return Engine
     */
    protected function engine(): Engine
    {
        return $this->_engine;
    }

    /**
     * The constructor
     *
     * @param string $tableName Table name
     */
    public function __construct(Engine $engine, ?int $id = null)
    {
        $this->_engine = $engine;

        $this->_initialize();
        if (!$this->_annotationsReader()) {
            logError(
                sprintf(
                    'Tto - Table object %s with bad DocBook annotations!',
                    get_class($this),
                ),
                __FILE__,
                __LINE__
            );

            abort(500);
        }

        if (!$this->hasId()) {
            logError(
                sprintf(
                    'Tto - Table object %s have not autoincremented `id` field!',
                    get_class($this),
                ),
                __FILE__,
                __LINE__
            );

            abort(500);
        }

        if ($this->hasId() && !is_null($id)) {
            $this->fromId($id)->get();
        }
    }

    /**
     * Get property value
     *
     * @param string $name Name of the property
     * @return mixed Property value
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->_fields)) {
            return $this->_fields[$name]['value'];
        }

        throw (new InvalidArgumentException("Bad property name: $name"));
        return null;
    }

    /**
     * Set property value
     *
     * @param string $name Name of the property
     * @param [type] $value Value of the property
     */
    public function __set(string $name, $value)
    {
        $name = trim($name);
        if (!array_key_exists($name, $this->_fields)) {
            throw (new InvalidArgumentException("Bad property name `$name`"));
            return;
        }

        if ($this->hasId() && $name == 'id') {
            throw (new InvalidArgumentException("Readonly `$name` property"));
            return;
        }

        $wantedType = gettype($this->_fields[$name]['value']);
        $type = gettype($value);
        if ($type != $wantedType) {
            throw (new InvalidArgumentException("Bad property `$name` value type `$type`. Expected `$wantedType`"));
            return;
        }

        $this->_fields[$name]['value'] = $value;
        $this->_updated[] = $name;
    }

    /**
     * Return if a property exists
     *
     * @param string $name Name of the property
     * @return boolean true, if property exists, else, false
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->_fields);
    }

    /**
     * Return a field value by his name
     *
     * @param string $name Field name
     * @return void
     */
    public function getFieldValue(string $name)
    {
        return $this->__get($name);
    }

    /**
     * Return the object name
     *
     * @return string Object name
     */
    public function name(): string
    {
        return $this->_tableName;
    }

    /**
     * Return all columns name
     *
     * @return array Columns name
     */
    public function columns(): array
    {
        return array_keys($this->_fields);
    }

    /**
     * Reset fields to defaults
     *
     * @return Tto
     */
    public function reset(bool $resetField = true): Tto
    {
        if ($resetField) {
            $this->_fields = $this->_defaults;
        }

        $this->_offset = -1;
        $this->_limit = -1;
        $this->_assoc = [];
        $this->_order = [];
        $this->_updated = [];

        return $this;
    }

    /**
     * Return if column named 'id' exists
     *
     * @return boolean true, if exists, else, false
     */
    public function hasId(): bool
    {
        return array_key_exists('id', $this->_fields);
    }

    /**
     * Take number of elements
     *
     * @param integer $limit Number of elements. -1 for no limit.
     * @return Tto
     */
    public function take(int $limit = -1): Tto
    {
        $this->_limit = $limit >= -1 ? $limit : -1;

        return $this;
    }

    /**
     * Take elements at
     *
     * @param integer $offset Offset to start
     * @return Tto
     */
    public function offset(int $offset = -1): Tto
    {
        $this->_offset = $offset >= -1 ? $offset : -1;

        return $this;
    }

    /**
     * Ascending order by
     *
     * @param string $name Order by
     * @return Tto
     */
    public function orderAsc(string $name): Tto
    {
        $name = trim($name);
        if (!array_key_exists($name, $this->_fields)) {
            return $this;
        }

        $this->_order = [$name, 'ASC'];
    }

    /**
     * Descending order by
     *
     * @param string $name Order by
     * @return Tto
     */
    public function orderDesc(string $name): Tto
    {
        $name = trim($name);
        if (!array_key_exists($name, $this->_fields)) {
            return $this;
        }

        $this->_order = [$name, 'DESC'];
    }

    /**
     * Fetch datas from prepared statement
     *
     * @return Tto
     */
    public function get(): ?Tto
    {
        try {
            $stmt = $this->_toGet();
            if (is_null($stmt)) {
                return null;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }

            $this->_assoc = $result;
            if (!$this->_assoc) {
                return null;
            }

            return $this->populate();
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Tto - Table object %s with error: (%s) %s',
                    get_class($this),
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return null;
        }
    }

    /**
     * Get all elements
     *
     * @return Tto[] List of objects found
     */
    public function all(): iterable
    {
        try {
            $stmt = $this->_toGet();
            if (is_null($stmt)) {
                return [];
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$result) {
                return [];
            }

            $rows = [];
            foreach ($result as $row) {
                $cls = get_class($this);
                $instance = new $cls($this->engine());

                $rows[] = $instance->populate($row);
            }

            return $rows;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Tto - Table object %s with error: (%s) %s',
                    get_class($this),
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return [];
        }
    }

    /**
     * Get from id
     *
     * @param integer $id The wanted id
     * @return Tto
     */
    public function fromId(int $id): Tto
    {
        if (!$this->hasId()) {
            return null;
        }

        $query = sprintf('SELECT * FROM %s WHERE BINARY id = :id', $this->_tableName);
        $params = ['id' => $id];

        $this->_query = ['query' => $query, 'params' => $params];

        $this->reset(false);
        $this->_limit = 1;

        return $this;
    }

    /**
     * Get from condition
     *
     * @param string $name Name of field to compare
     * @param string $operator Operator (eg: = | != | < | > | <= | >= )
     * @param $value Value to compare
     * @param boolean $binary Is a binary comparaison?
     * @return Tto
     */
    public function where(string $name, string $operator, $value, bool $binary = false): Tto
    {
        $query = sprintf('SELECT * FROM %s WHERE', $this->_tableName);

        $prepared = $this->_prepareWhere($name, $operator);
        if (is_null($prepared)) {
            return $this;
        }
        list($name, $operator) = $prepared;

        $query .= sprintf(
            '%1$s %2$s %3$s :%2$s',
            ($binary ? ' BINARY' : ''),
            $name,
            $operator
        );
        $params = [$name => $value];

        $this->_query = ['query' => $query, 'params' => $params];

        $this->reset(false);
        $this->_limit = 1;

        return $this;
    }

    /**
     * Get or an another condition
     *
     * @param string $name Name of field to compare
     * @param string $operator Operator (eg: = | != | < | > | <= | >= )
     * @param $value Value to compare
     * @return Tto
     */
    public function orWhere(string $name, string $operator, $value): Tto
    {
        $prepared = $this->_prepareWhere($name, $operator);
        if (is_null($prepared)) {
            return $this;
        }
        list($name, $operator) = $prepared;

        $query = sprintf(
            ' OR %1$s %2$s :%1$s',
            $name,
            $operator
        );
        $params[$name] = $value;

        $this->_query['query'] .= $query;
        $this->_query['params'] = array_merge($this->_query['params'], $params);

        return $this;
    }

    /**
     * Get and an another condition
     *
     * @param string $name Name of field to compare
     * @param string $operator Operator (eg: = | != | < | > | <= | >= )
     * @param $value Value to compare
     * @return Tto
     */
    public function andWhere(string $name, string $operator, $value): Tto
    {
        $prepared = $this->_prepareWhere($name, $operator);
        if (is_null($prepared)) {
            return $this;
        }
        list($name, $operator) = $prepared;

        $query = sprintf(
            ' AND %1$s %2$s :%1$s',
            $name,
            $operator
        );
        $params[$name] = $value;

        $this->_query['query'] .= $query;
        $this->_query['params'] = array_merge($this->_query['params'], $params);

        return $this;
    }

    /**
     * Populate this object with assoc array
     *
     * @param array $assoc Assoc array
     * @return Tto 
     */
    public function populate(?array $assoc = null): Tto
    {
        $this->_assoc = $assoc ?? $this->_assoc;
        foreach ($this->_assoc as $name => $value) {
            if (!array_key_exists($name, $this->_fields)) {
                continue;
            }

            $value = $this->_convert($value, $this->_fields[$name]['type']);
            if (gettype($this->_fields[$name]['value']) != gettype($value)) {
                continue;
            }

            $this->_fields[$name]['value'] = $value;
        }

        return $this;
    }

    /**
     * Update the table
     *
     * @return boolean true, the table is updated, else, false
     */
    public function update(): bool
    {
        if (count($this->_updated) == 0 || !$this->hasId()) {
            return false;
        }

        $set = [];
        $params = [];
        foreach ($this->_updated as $name) {
            $params[$name] = $this->_toSqlValue($name);

            $set[] = sprintf('%1$s = :%1$s', $name);
        }
        $this->_updated = [];

        try {
            $query = sprintf(
                'UPDATE %s SET %s WHERE BINARY id = :id',
                $this->_tableName,
                implode(',', $set)
            );

            $conn = $this->engine()->db()->connection();

            $stmt = $conn->prepare($query);

            $stmt->bindValue(
                ':id',
                $this->_toSqlValue('id'),
                $this->_fieldToSqlParamType('id')
            );

            foreach ($params as $key => $value) {
                $stmt->bindValue(
                    ':' . $key,
                    $value,
                    $this->_fieldToSqlParamType($key)
                );
            }

            if (!$stmt->execute()) {
                return false;
            }

            $cnt = $stmt->rowCount();
            if ($cnt <= 0) {
                $this->fromId($this->id);

                return false;
            }

            return true;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Tto - Table object %s with error: (%s) %s',
                    get_class($this),
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return false;
        }
    }

    /**
     * Store into the table
     *
     * @return boolean true, if stored, else, false
     */
    public function store(): bool
    {
        if (count($this->_updated) == 0) {
            return false;
        }

        $set = array_keys($this->_fields);
        if ($this->hasId() && $this->id == -1) {
            unset($set['id']);
        }
        $values = array_map(fn ($name) => ':' . $name, $set);
        $this->_updated = [];

        try {
            $query = sprintf(
                '%s INTO %s (%s) VALUES %s',
                ($this->id == -1 ? 'INSERT' : 'REPLACE'),
                $this->_tableName,
                implode(',', $set),
                implode(',', $values)
            );

            $conn = $this->engine()->db()->connection();

            $stmt = $conn->prepare($query);
            foreach ($set as $key) {
                $stmt->bindValue(
                    ':' . $key,
                    $this->_toSqlValue($key),
                    $this->_fieldToSqlParamType($key)
                );
            }

            if (!$stmt->execute()) {
                return false;
            }

            $id = $conn->lastInsertId();
            if ($id > -1) {
                $this->fromId($id)->get();

                return true;
            }

            return false;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Tto - Table object %s with error: (%s) %s',
                    get_class($this),
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return false;
        }
    }

    /**
     * Delete rows
     *
     * @return boolean true, if stored, else, false
     */
    public function delete(): bool
    {
        if (!$this->hasId()) {
            return false;
        }

        try {
            $query = sprintf(
                'DELETE FROM %s WHERE id = :id',
                $this->_tableName
            );

            $conn = $this->engine()->db()->connection();

            $stmt = $conn->prepare($query);

            $stmt->bindValue(
                ':id',
                $this->_toSqlValue('id'),
                $this->_fieldToSqlParamType('id')
            );

            if (!$stmt->execute()) {
                return false;
            }

            $cnt = $stmt->rowCount();
            if ($cnt > 0) {
                $this->reset();

                return true;
            }

            return false;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Tto - Table object %s with error: (%s) %s',
                    get_class($this),
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return false;
        }
    }
}
