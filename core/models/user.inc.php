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

namespace Core\Models;

use Core\Engine;
use Core\Libs\Tto;
use PDO;

/**
 * An user object
 */
class User extends Tto
{

    private Engine $_engine;

    /**
     * The constructor
     * 
     * @param Engine $engine
     */
    public function __construct(Engine $engine)
    {
        parent::__construct('users');

        $this->_engine = $engine;
    }

    /**
     * Load an user by Id
     *
     * @param integer $id Id of the user
     * @return boolean true, user loaded, else, false
     */
    public function byId(int $id): ?Tto
    {
        $result = $this->fetch(
            'SELECT * FROM :tableName WHERE id = :id LIMIT 1',
            [
                ['id', $id, PDO::PARAM_INT],
            ]
        );
        if (is_null($result) || !is_array($result) || count($result) != 1) {
            return null;
        }

        return $this->populate($result);
    }
}
