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
 * Auto import class
 *
 * @return void
 */
function autoImport()
{
    spl_autoload_register(
        function (string $class) {
            $items = explode('\\', $class);
            array_walk($items, function (&$value) {
                $value = lcfirst($value);
            });

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
}

/**
 * Abort treatment
 *
 * @param integer $code HTTP error code
 * @return void
 */
function abort(int $code)
{
    $appType = Env::get('APP_TYPE', 'web');

    if ($appType == 'api') {
        sendJSON(['httpError' => $code]);
    }

    if ($appType == 'web') {
        http_response_code($code);
        exit;
    }
}

/**
 * Create slug format of text
 *
 * @param string $text Text to convert
 * @param string $divider Slug divider, default '-'
 * @return string Slug created or empty if very bad text
 */
function makeSlug(string $text, string $divider = '-'): string
{
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, $divider);
    $text = preg_replace('~-+~', $divider, $text);
    $text = strtolower($text);

    return $text;
}

/**
 * Convert flat uri to an array and sanitize her
 *
 * @param string $uri Flat uri to convert
 * @return array Uri converted in array
 */
function uriToArray(string $uri): array
{
    $ret = [];

    $ret = explode('/', rawurldecode($uri));

    array_walk(
        $ret,
        function (&$value, string $key) {
            if (preg_match('/\{([0-9a-zA-Z_-]+):([a-z]+)\}/', $value, $result) !== false) {
                if (count($result) > 0) {
                    $value = [
                        'varname' => $result[1],
                        'var' => str_replace($result[0], '$' . $result[1], $value),
                        'type' => $result[2],
                    ];

                    return;
                }
            }

            $value = makeSlug($value);
        }
    );

    $ret = array_values(array_filter(
        $ret,
        fn ($value, string $key): bool => trim($key) != '' && ((is_string($value) && trim($value) != '') || is_array($value)),
        ARRAY_FILTER_USE_BOTH
    ));

    return $ret;
}

/**
 * Reflat uri from an exploded array uri
 *
 * @param array $uri Exploded uri
 * @param array $params Uri params to fill
 * @return string Flatted Uri
 */
function arrayToUri(array $uri, array $params = []): string
{
    $finalUri = $uri;
    for ($i = 0; $i < count($uri); $i++) {
        $item = $uri[$i];

        if (!is_array($item)) {
            $finalUri[$i] = $item;
            continue;
        }

        if (!array_key_exists($item['varname'], $params)) {
            $finalUri[$i] = '{' . $item['varname'] . ':' . $item['type'] . '}';
            continue;
        }

        $finalUri[$i] = strval($params[$item['varname']]);
    }

    return implode('/', $finalUri);
}

/**
 * Return the base URL of the current page
 *
 * @return string Base URL found
 */
function baseUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    return $protocol . $host;
}

/**
 * Send a JSON response
 *
 * @param array $response Response array formated to send
 * @return void
 */
function sendJSON(array $response): void
{
    header('Content-type: application/json');
    echo json_encode($response);
    exit;
}
