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

namespace Core\Libs\Drivers;

use Core\Libs\Env;
use PDO;
use PDOException;
use Core\Libs\IDB;

use function Core\Libs\logError;

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
                Env::get('DATABASE_NAME'),
                Env::get('DATABASE_HOST'),
                Env::get('DATABASE_PORT')
            );

            // return the MySQL PDO object
            $pdo = new PDO(
                $connectionString,
                Env::get('DATABASE_USERNAME'),
                Env::get('DATABASE_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_FOUND_ROWS      => true,
                ]
            );

            // do not convert int and float to string by PDO fetch routine
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

            // return the new PDO object
            return $pdo;
        } catch (PDOException $pdoe) {
            logError(
                sprintf(
                    'MySQL PDO connection error: (%s) %s',
                    $pdoe->getCode(),
                    $pdoe->getMessage()
                ),
                $pdoe->getFile(),
                $pdoe->getLine()
            );

            header('location: /500');
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
