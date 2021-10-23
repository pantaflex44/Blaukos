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

namespace Core;

/**
 * Autoload all objects files
 */
spl_autoload_register(
    function (string $class) {
        $items = explode('\\', strtolower($class));

        $filename = array_pop($items);

        $path = '/' . implode('/', $items);
        if (strlen($path) > 0 && substr($path, -1, 1) != '/') {
            $path .= '/';
        }

        $filepath = '..' . $path . '{**/,}' . $filename . '.inc.php';
        $files = glob($filepath, GLOB_BRACE);

        if (count($files) == 1) {
            require_once $files[0];
        }
    }
);

use Core\Libs\Env;
use Core\Libs\DB;
use Core\Libs\IDB;

/**
 * Main public entry point
 */
class Engine
{

    private ?IDB $_db = null;

    /**
     * Return the database manager
     *
     * @return IDB|null The database manager or null 
     */
    public function db(): ?IDB
    {
        return $this->_db;
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        // load .env files
        Env::load();

        // load the database manager
        $this->_db = DB::instance();
    }
}
