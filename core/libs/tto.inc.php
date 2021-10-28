<?php

/**
 * KuntoManager - Logiciel de gestion de salles de sports
 * Copyright (C) 2021 Christophe LEMOINE
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Core\Libs;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOStatement;

/**
 * Manage settings
 */
class Tto
{

    private string $_tableName;
    private array $_olds = [];
    private array $_fields = [];

    /**
     * The constructor
     *
     * @param string $tableName Table name
     */
    public function __construct(string $tableName)
    {
        $this->_tableName = $tableName;
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
            return $this->_fields;
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
    protected function fetch(string $query, array $params = []): ?array
    {
        try {
            $conn = $this->_engine->db()->connection();

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tableName', $this->_tableName, PDO::PARAM_STR);

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
            if (Env::get('APP_DEBUG', 'true') == 'true') {
                $errorMessage = sprintf(
                    '[%s] Table To Object, fetch error: %s {file: %s at line %d}',
                    Env::get('APP_NAME'),
                    $ex->getMessage(),
                    __FILE__,
                    __LINE__
                );
                error_log($errorMessage, 0);
            }

            return null;
        }
    }
}
