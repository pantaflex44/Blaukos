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

namespace Core\Libs\Drivers;

use PDO;
use PDOException;
use Core\Libs\IDB;

/**
 * MySQL Database manager
 */
final class Mysql implements IDB
{

    private static ?PDO $_dbConnection = null;

    /**
     * Intialize the database connection
     *
     * @return PDO A PDO connection or null
     */
    private static function _initialize(): PDO
    {
        try {
            // construct connection string
            $connectionString = sprintf(
                'mysql:dbname=%s;host=%s;port=%d;charset=utf8',
                getenv('DATABASE_NAME'),
                getenv('DATABASE_HOST'),
                getenv('DATABASE_PORT')
            );

            // return the MySQL PDO object
            $pdo = new PDO(
                $connectionString,
                getenv('DATABASE_USERNAME'),
                getenv('DATABASE_PASSWORD'),
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                )
            );

            // do not convert int and float to string by PDO fetch routine
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

            // return the new PDO object
            return $pdo;
        } catch (PDOException $pdoe) {
            $errorMessage = sprintf(
                '[%s] MySQL PDO connection error: (%s) %s {file: %s}',
                getenv('APP_NAME'),
                $pdoe->getCode(),
                $pdoe->getMessage(),
                __FILE__
            );
            error_log($errorMessage, 0);

            http_response_code(500);
            exit;
        }
    }

    /**
     * Get the database connection
     *
     * @return PDO The database connection
     */
    public static function connection(): PDO
    {
        // if is the first connection
        if (is_null(self::$_dbConnection)) {
            self::$_dbConnection = self::_initialize();
        } else {
            // else, if has a memorized connection
            // if not, disable error_reporting
            $oldErrLvl = error_reporting(0);

            try {
                // try the connection with a blank query
                self::$_dbConnection->query("SELECT 1");
            } catch (\PDOException $pdoe) {
                // if not a valid connection, try to reload
                self::$_dbConnection = self::_initialize();
            }

            // rollback error_reporting status
            error_reporting($oldErrLvl);
        }

        return self::$_dbConnection;
    }
}
