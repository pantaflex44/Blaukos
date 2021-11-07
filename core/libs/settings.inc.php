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
            logError(
                sprintf(
                    'Unable to load settings: %s',
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

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
            logError(
                sprintf(
                    'Unable to save settings: %s',
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );

            return false;
        }
    }
}
