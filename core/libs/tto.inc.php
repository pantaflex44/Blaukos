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
use Exception;
use InvalidArgumentException;
use PDO;

/**
 * Manage settings
 */
class Tto
{

    private Engine $_engine;
    private string $_tableName;
    private array $_olds = [];
    private array $_fields = [];

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
    public function __construct(Engine $engine, string $tableName, ?int $id = null)
    {
        $this->_engine = $engine;
        $this->_tableName = $tableName;

        if (!is_null($id)) {
            if (is_null($this->fromId($id))) {
                logError(
                    sprintf(
                        "Object with id:%d not found in table '%s'",
                        $id,
                        $this->_tableName
                    ),
                    __FILE__,
                    __LINE__
                );

                $this->engine()->route()->call('500');
            }
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
            return $this->_fields[$name];
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
        if (array_key_exists($name, $this->_fields)) {
            $this->_olds[$name] = $this->_fields[$name];
        } else {
            $this->_olds[$name] = null;
        }

        $this->_fields[$name] = $value;
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
     * Return the object name
     *
     * @return string Object name
     */
    public function name(): string
    {
        return $this->_tableName;
    }

    /**
     * Return all fields
     *
     * @return array Array of fields
     */
    public function fields(): array
    {
        return $this->_fields;
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
     * Populate this object with assoc array
     *
     * @param array $assoc Assoc array
     * @return Tto 
     */
    public function populate(array $assoc): Tto
    {
        foreach ($assoc as $name => $value) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Copy an object to this object
     *
     * @param Tto $ttoObject Object to copy
     * @return Tto
     */
    public function copy(Tto $ttoObject): Tto
    {
        foreach ($ttoObject->fields() as $name => $value) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Clone an object with this
     *
     * @param Tto $ttoObject Object to clone
     * @return Tto
     */
    public function clone(Tto $ttoObject): Tto
    {
        $this->_fields = [];

        return $this->copy($ttoObject);
    }

    /**
     * Fetch datas
     *
     * @param string $query SQL Query to fetch (eg: SELECT * FROM :tableName WHERE id = :id AND name = :name)
     * @param array $params Array of parameters (eg: [['id', 1, PDO::PARAM_INT], ['name', 'bob', PDO::PARAM_STR]])
     * @return array|mixed Return value or null if error
     */
    public function fetch(string $query, array $params = []): ?array
    {
        try {
            $conn = $this->_engine->db()->connection();

            $stmt = $conn->prepare(str_replace(':tableName', $this->_tableName, $query));

            foreach ($params as $param) {
                if (count($param) != 3) {
                    continue;
                }

                $stmt->bindParam(':' . $param[0], $param[1], $param[2]);
            }

            if (!$stmt->execute()) {
                return null;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }

            return $result;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Table To Object, fetch error: %s',
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return null;
        }
    }

    /**
     * Insert datas
     *
     * @param string $query SQL Query to insert (eg: INSERT INTO :tableName (col1, col2, col3) VALUES (:value1, :value2, :value3))
     * @param array $params Array of parameters (eg: [['id', 1, PDO::PARAM_INT], ['name', 'bob', PDO::PARAM_STR]])
     * @return int|mixed Inserted id or null if error
     */
    public function execute(string $query, array $params = []): ?int
    {
        try {
            $conn = $this->_engine->db()->connection();

            $stmt = $conn->prepare(str_replace(':tableName', $this->_tableName, $query));

            foreach ($params as $param) {
                if (count($param) != 3) {
                    continue;
                }

                $stmt->bindValue(':' . $param[0], $param[1], $param[2]);
            }

            if (!$stmt->execute()) {
                return null;
            }

            // if is a insert query, return the row id
            $id = $conn->lastInsertId();

            // if is an update query, only rowCount availlable
            if ($id < 1) {
                $id = $stmt->rowCount();
            }

            return $id;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Table To Object, insert or update error: %s',
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return null;
        }
    }

    /**
     * Load an item by Id
     *
     * @param integer $id Id of the item
     * @return Tto|null
     */
    public function fromId(int $id): ?Tto
    {
        $result = $this->fetch(
            "SELECT * FROM :tableName WHERE id = :id LIMIT 1",
            [
                ['id', $id, PDO::PARAM_INT],
            ]
        );

        if (is_null($result) || !is_array($result) || count($result) == 0) {
            return null;
        }

        return $this->populate($result);
    }

    /**
     * Reload items
     *
     * @return Tto|null
     */
    public function reload(): ?Tto
    {
        if (!isset($this->id)) {
            return $this;
        }

        return $this->fromId($this->id);
    }
}
