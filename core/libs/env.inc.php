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

/**
 * Manage .env files
 */
class Env
{

    /**
     * Load all .env files
     *
     * @return void
     */
    public static function load()
    {
        $files = glob('../{**/,}.env*', GLOB_BRACE);

        foreach ($files as $file) {
            if (!is_readable($file)) {
                continue;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (substr($line, 0, 1) == '#') {
                    continue;
                }

                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv(sprintf('%s=%s', $key, $value));
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    /**
     * Get an environment value
     *
     * @param string $key Environment key
     * @param string $default Default value if not exists
     * @return string Value of the environement key
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        return $value;
    }
}
