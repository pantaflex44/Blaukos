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

use Core\Engine;
use Exception;

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
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;

        $this->_method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper(trim(filter_var($_SERVER['REQUEST_METHOD'], FILTER_UNSAFE_RAW)))
            : 'GET';

        $uri = isset($_SERVER['REQUEST_URI'])
            ? trim(filter_var($_SERVER['REQUEST_URI'], FILTER_UNSAFE_RAW))
            : '/';
        $this->_uri = uriToArray($uri);

        $this->_load();
    }

    /**
     * Load cached routes
     *
     * @return boolean true, if correctly loaded, else, false
     */
    private function _load(): bool
    {
        if (
            !file_exists(self::FILE)
            || Env::get('APP_USECACHE', 'false') != 'true'
        ) {
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
                $this->add($name, $route['type'], $route['method'], $route['uri'], $route['callback']);
            }

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Add new route condition
     *
     * @param string $name Route name
     * @param string $type Allowed application types (eg: 'web', 'api', 'web,api')
     * @param string $method HTTP method ('GET', 'POST', 'DELETE', 'PUT')
     * @param string $uri Uri to query start with a / (eg: / | /post/{varname:type}). 'varname' is the varname without the $, type is a php gettype() return value @see https://www.php.net/manual/fr/function.gettype.php
     * @param array $callback Array of the callable controller (eg: [HomeController::class, 'index'])
     * @return void
     */
    public function add(string $name, string $type, string $method, string $uri, array $callback): bool
    {
        if (stripos($type, Env::get('APP_TYPE'), 0) === false) {
            return false;
        }

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
     * Compute annotation found by Annotation system
     *
     * @param string $className
     * @param string $methodName
     * @param array $matches
     * @return void
     */
    public function computeAnnotation(string $className, string $methodName, array $matches): void
    {
        if (count($matches) != 5) {
            return;
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            $routeName = $matches[1][$i];
            $routeType = $matches[2][$i];
            $routeMethod = $matches[3][$i];
            $routeUri = $matches[4][$i];
            $routeCallback = [$className, $methodName];

            $this->add(
                $routeName,
                $routeType,
                $routeMethod,
                $routeUri,
                $routeCallback
            );
        }

        $this->_save();
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
     * Map routes and call a controller
     *
     * @return void
     */
    public function map()
    {
        $routes = array_values(array_filter(
            $this->_routes,
            fn($route) => ($route['method'] == $this->_method && count($route['uri']) == count($this->_uri))
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
        }
    }

    /**
     * Call a method from a conttoller with params
     *
     * @param callable $callback Callback to execute
     * @param array $params Array of callback parameters
     * @return void
     */
    private function _call(callable $callback, array $params = []): bool
    {
        $calling = [$callback[0], '__calling'];
        if (is_callable($calling)) {
            call_user_func($calling, $callback);
        }

        $success = (call_user_func_array($callback, $params) === false) ? false : true;

        if (!$success) {
            logError(
                sprintf(
                    'Route callback error: %s',
                    var_export($callback, true)
                ),
                __FILE__,
                __LINE__
            );

            $this->call('500');
        }

        $called = [$callback[0], '__called'];
        if (is_callable($called)) {
            call_user_func($called, $callback);
        }

        return $success;
    }

    /**
     * Call controller of route by her name
     *
     * @param string $name Name of the route
     * @param array $params Params to fill, optionnal
     * @return void
     */
    public function call(string $name, array $params = []): bool
    {
        $name = makeSlug($name);

        if (!array_key_exists($name, $this->_routes)) {
            $code = intval($name);
            if ($code != 0) {
                abort($code);
            }

            return false;
        }

        return $this->_call($this->_routes[$name]['callback'], $params);
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
     * Call controller of route by her name to redirect
     *
     * @param string $name Name of the route
     * @param int $afterDelay Delay in seconds then redirect to route name
     * @return void
     */
    public function redirect(string $name, int $afterDelay = 0)
    {
        $name = makeSlug($name);

        if (!array_key_exists($name, $this->_routes)) {
            return;
        }

        if ($this->_routes[$name]['method'] != 'GET') {
            return;
        }

        $uri = arrayToUri($this->_routes[$name]['uri']);
        if ($afterDelay == 0) {
            header("location: /$uri");
        } else {
            header("refresh: $afterDelay; url=/$uri");
        }
        exit;
    }

    /**
     * Purge and re-scan controllers
     *
     * @return void
     */
    public function reset()
    {
        $this->purge();

        Annotations::scan($this->_engine);
    }

    /**
     * Purge cached routes
     *
     * @return void
     */
    public function purge()
    {
        @unlink(self::FILE);
    }
}