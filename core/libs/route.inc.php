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
use ReflectionMethod;

use function Core\Libs\uritoarray;

/**
 * Manage routes
 */
class Route
{
    private const FILE = __DIR__ . '/../datas/routes.datas';

    private Engine $_engine;

    private string $_method = 'GET';
    private array $_uri = [];
    private array $_routes = [];

    /**
     * Call a method from a conttoller with params
     *
     * @param callable $callback Callback to execute
     * @param array $params Array of callback parameters
     * @return void
     */
    private function _call(callable $callback, array $params = [])
    {
        if (call_user_func_array($callback, $params) === false) {
            if (Env::get('APP_DEBUG', 'true') == 'true') {
                $errorMessage = sprintf(
                    '[%s] Route callback error: %s {file: %s at line %d}',
                    getenv('APP_NAME'),
                    var_export($callback),
                    __FILE__,
                    __LINE__
                );
                error_log($errorMessage, 0);
            }

            abort(500);
        }
    }

    /**
     * Scan declared controllers to auto add routes from methods comment
     *
     * @return void
     */
    private function _scan()
    {
        $ctrlFiles = glob(__DIR__ . '/../controllers/*Controller.inc.php');

        foreach ($ctrlFiles as $file) {
            if (preg_match('/(.*)\/(.+)\.inc\.php/', $file, $match)) {
                if (!is_array($match) || count($match) != 3) {
                    continue;
                }

                $className = '\\Core\\Controllers\\' . ucfirst($match[2]);
                $cls = new ReflectionClass($className);
                if (!$cls->isSubclassOf('\\Core\\Libs\\Controller')) {
                    continue;
                }

                $methods = $cls->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $doc = $method->getDocComment();
                    if (preg_match_all('/@route[\s\t]+\'(.+)\'[\s\t]+\'(.+)\'[\s\t]+\'(.+)\'/', $doc, $matches)) {
                        if (!is_array($matches) || count($matches) != 4) {
                            continue;
                        }

                        for ($i = 0; $i < count($matches[0]); $i++) {
                            $routeName = $matches[1][$i];
                            $routeMethod = $matches[2][$i];
                            $routeUri = $matches[3][$i];
                            $routeCallback = [$className, $method->name];

                            $this->add(
                                $routeName,
                                $routeMethod,
                                $routeUri,
                                $routeCallback
                            );
                        }
                    }
                }
            }
        }

        if (Env::get('APP_USECACHE', 'false') == 'true') {
            $this->_save();
        }
    }

    /**
     * Save routes for caching
     *
     * @return void
     */
    private function _save()
    {
        $routes = [];

        foreach ($this->_routes as $name => $route) {
            $method = $route['method'];
            $uri = '/' . arrayToUri($route['uri']);
            $callback = [
                get_class($route['callback'][0]),
                $route['callback'][1],
            ];

            $routes[$name] = [
                'method' => $method,
                'uri' => $uri,
                'callback' => $callback,
            ];
        }

        $srz = serialize($routes);
        @file_put_contents(self::FILE, $srz);
    }

    /**
     * Load cached routes
     *
     * @return boolean true, if correctly loaded, else, false
     */
    private function _load(): bool
    {
        if (!file_exists(self::FILE)) {
            return false;
        }

        try {
            $srz = file_get_contents(self::FILE);
            if ($srz === false) {
                return false;
            }

            $routes = unserialize($srz);

            $this->_routes = [];
            foreach ($routes as $name => $route) {
                $this->add($name, $route['method'], $route['uri'], $route['callback']);
            }

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

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

        $cacheLoaded = false;
        if (Env::get('APP_USECACHE', 'false') == 'true') {
            $cacheLoaded = $this->_load();
        }
        if (!$cacheLoaded) {
            $this->_scan();
        }
    }

    /**
     * Add new route condition
     *
     * @param string $name Route name
     * @param string $method HTTP method ('GET', 'POST', 'DELETE', 'PUT')
     * @param string $uri Uri to query start with a / (eg: / | /post/{varname:type}). 'varname' is the varname without the $, type is a php gettype() return value @see https://www.php.net/manual/fr/function.gettype.php 
     * @param array $callback Array of the callable controller (eg: [HomeController::class, 'index'])
     * @return void
     */
    public function add(string $name, string $method, string $uri, array $callback): bool
    {
        $method = strtoupper(trim($method));
        if ($method != 'GET' && $method != 'POST' && $method != 'DELETE' && $method != 'PUT') {
            return false;
        }

        if (count($callback) != 2 || !is_string($callback[0]) || !is_string($callback[1])) {
            return false;
        }

        $cls = new $callback[0]($this->_engine);
        if (is_null($cls)) {
            return false;
        }

        $caller = [$cls, $callback[1]];
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
                $this->_call($route['callback'], $params);
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

        $this->_call($this->_routes[$name]['callback'], $params);
    }
}
