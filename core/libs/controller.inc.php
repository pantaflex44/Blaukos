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

use Core\Engine;

/**
 * Base controller structure
 */
class Controller
{

    private Engine $_engine;
    private static array $_controllers = [];

    /**
     * Get the engine
     *
     * @return Engine Engine object
     */
    public function engine(): Engine
    {
        return $this->_engine;
    }

    /**
     * List all known controllers
     *
     * @return array Known controllers
     */
    public static function controllers(): array
    {
        return self::$_controllers;
    }

    /**
     * Constructor
     *
     * @param Engine $engine An engine instance
     */
    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;

        $ctrlName = get_class($this);
        if (!in_array($ctrlName, self::$_controllers)) {
            self::$_controllers[] = $ctrlName;
        }
    }
}
