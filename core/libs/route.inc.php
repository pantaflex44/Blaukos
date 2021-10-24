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
use Exception;
use ReflectionClass;
use ReflectionException;

use function Core\Libs\uritoarray;

/**
 * Manage routes
 */
class Route
{

    private Engine $_engine;

    private string $_method = 'GET';
    private array $_uri = [];
    private array $_routes = [];

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine  = $engine;

        $this->_method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper(trim(filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_STRING)))
            : 'GET';

        $uri = isset($_SERVER['REQUEST_URI'])
            ? trim(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_STRING))
            : '/';
        $this->_uri = uriToArray($uri);
    }

    /**
     * Add new route condition
     *
     * @param string $name Route name
     * @param string $method HTTP method ('GET', 'POST', 'DELETE', 'PUT')
     * @param string $uri Uri to query start with a / (eg: / | /post/{varname:type}). 'varname' is the varname without the $, type is a php gettype() return value @see https://www.php.net/manual/fr/function.gettype.php 
     * @param array $route Array of the callable controller (eg: [HomeController::class, 'index'])
     * @return void
     */
    public function add(string $name, string $method, string $uri, array $route): bool
    {
        $method = strtoupper(trim($method));
        if ($method != 'GET' && $method != 'POST' && $method != 'DELETE' && $method != 'PUT') {
            return false;
        }

        if (count($route) != 2 || !is_string($route[0]) || !is_string($route[1])) {
            return false;
        }

        $cls = new $route[0]($this->_engine);
        if (is_null($cls)) {
            return false;
        }

        $caller = [$cls, $route[1]];
        if (!is_callable($caller, false, $callback)) {
            return false;
        }

        $name = makeSlug($name);
        $this->_routes[$name] = [
            'method' => $method,
            'callback' => $caller,
            'uri' => uritoarray($uri),
        ];

        return true;
    }

    /**
     * Map routes and call a controller
     *
     * @return void
     */
    public function map()
    {
        $routes = array_values(array_filter(
            $this->_routes,
            fn ($route) => $route['method'] == $this->_method && count($route['uri']) == count($this->_uri)
        ));

        $callable = false;
        foreach ($routes as $route) {
            if (count($route['uri']) == 0) {
                $callable = true;
            }

            $params = [];

            for ($i = 0; $i < count($route['uri']); $i++) {
                $item = $route['uri'][$i];

                // if value of item from rule equals value of item from uri at same position, 
                // it's good and continue
                if ($item === $this->_uri[$i]) {
                    $callable = true;
                    continue;
                }

                // else, if those items have same types, but not their values, 
                // it's bad and break
                if (gettype($item) == gettype($this->_uri[$i])) {
                    $callable = false;
                    break;
                }

                // if item from actual rule isn't an array,
                // it's an error, so break this rule
                if (!is_array($item)) {
                    $callable = false;
                    break;
                }

                // convert value of the item from actual uri to expected type
                $value = $this->_uri[$i];
                settype($value, $item['type']);

                // if conversion isn't correct,
                // it's not the good rule, so break this rule
                if (strval($value) !== $this->_uri[$i]) {
                    $callable = false;
                    break;
                }

                $params[$item['varname']] = $value;
                $callable = true;
            }

            if ($callable) {
                if (call_user_func_array($route['callback'], $params) === false) {
                    if (Env::get('APP_DEBUG', 'true') == 'true') {
                        $errorMessage = sprintf(
                            '[%s] Route callback error: %s {file: %s at line %d}',
                            getenv('APP_NAME'),
                            var_export($route['callback']),
                            __FILE__,
                            __LINE__
                        );
                        error_log($errorMessage, 0);
                    }

                    abort(500);
                }
                break;
            }
        }

        if (!$callable) {
            $this->call('404');
            exit;
        }
    }

    /**
     * Get the uri of an added route by her name
     *
     * @param string $name Name of the route
     * @param array $params Params to fill, optionnal
     * @param boolean $full true, full url returned, else false
     * @return string
     */
    public function get(string $name, array $params = [], bool $full = true): string
    {
        $name = makeSlug($name);

        if (!array_key_exists($name, $this->_routes)) {
            return '';
        }

        $uri = arrayToUri($this->_routes[$name]['uri'], $params);
        if ($uri != '') {
            $uri = '/' . $uri;
        }

        return $full ? baseUrl() . $uri : $uri;
    }

    /**
     * Call controller of route by her name
     *
     * @param string $name Name of the route
     * @param array $params Params to fill, optionnal
     * @return void
     */
    public function call(string $name, array $params = [])
    {
        $name = makeSlug($name);

        if (!array_key_exists($name, $this->_routes)) {
            return;
        }

        if (call_user_func_array($this->_routes[$name]['callback'], $params) === false) {
            if (Env::get('APP_DEBUG', 'true') == 'true') {
                $errorMessage = sprintf(
                    '[%s] Route callback error: %s {file: %s at line %d}',
                    getenv('APP_NAME'),
                    var_export($this->_routes[$name]['callback']),
                    __FILE__,
                    __LINE__
                );
                error_log($errorMessage, 0);
            }

            abort(500);
        }
    }
}
