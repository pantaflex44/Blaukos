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

/**
 * Manage settings
 */
class Settings
{

    private const FILE = __DIR__ . '/../datas/settings.json';
    private static ?array $_fields = [];

    /**
     * Load settings
     *
     * @return void
     */
    public static function load()
    {
        try {
            $json = @file_get_contents(self::FILE);
            self::$_fields = json_decode($json, true);
            if (is_null(self::$_fields)) {
                self::$_fields = [];
            }
        } catch (Exception $ex) {
            if (Env::get('APP_DEBUG', 'true') == 'true') {
                $errorMessage = sprintf(
                    '[%s] Unable to load settings: %s {file: %s at line %d}',
                    Env::get('APP_NAME'),
                    $ex->getMessage(),
                    __FILE__,
                    __LINE__
                );
                error_log($errorMessage, 0);
            }

            self::$_fields = [];
        }
    }

    /**
     * Get settings from key name
     *
     * @param string $key Key name
     * @param mixed $default Default value
     * @return mixed Value found or default
     */
    public static function get(string $key, $default)
    {
        if (array_key_exists($key, self::$_fields)) {
            return self::$_fields[$key];
        }

        return $default;
    }

    /**
     * Set value of key
     *
     * @param string $key Key name
     * @param mixed $value The value
     * @return void
     */
    public static function set(string $key, $value)
    {
        self::$_fields[$key] = $value;
    }

    /**
     * Save settings
     *
     * @return boolean true, settings saved, else, false on error
     */
    public static function save(): bool
    {
        try {
            $json = json_encode(self::$_fields);

            return file_put_contents(self::FILE, $json) !== false;
        } catch (Exception $ex) {
            if (Env::get('APP_DEBUG', 'true') == 'true') {
                $errorMessage = sprintf(
                    '[%s] Unable to save settings: %s {file: %s at line %d}',
                    Env::get('APP_NAME'),
                    $ex->getMessage(),
                    __FILE__,
                    __LINE__
                );
                error_log($errorMessage, 0);
            }

            return false;
        }
    }
}
