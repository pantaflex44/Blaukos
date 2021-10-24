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

use PDO;
use ReflectionClass;

/**
 * Manage databases
 */
class Database
{

    /**
     * Return new instance of a database manager
     *
     * @return IDB|null Database manager instance or null
     */
    public static function instance(): ?IDB
    {
        $filepath = __DIR__ . '/drivers/' . Env::get('DATABASE_DRIVER', 'mysql') . '.inc.php';
        if (!file_exists($filepath)) {
            return null;
        }

        $usePath = 'Core\\Libs\\Drivers\\' . ucfirst(Env::get('DATABASE_DRIVER', 'mysql'));
        $clsInstance = new ReflectionClass($usePath);

        return $clsInstance->newInstanceWithoutConstructor();
    }
}

/**
 * Database interface
 */
interface IDB
{
    public static function connection(): PDO;
}
