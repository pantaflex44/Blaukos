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

/**
 * Base controller structure
 */
class Controller
{

    private Engine $_engine;
    private static array $_controllers = [];

    private string $_appType = 'web';

    /**
     * Get the engine
     *
     * @return Engine Engine object
     */
    public function engine(): Engine
    {
        return $this->_engine;
    }

    /**
     * List all known controllers
     *
     * @return array Known controllers
     */
    public static function controllers(): array
    {
        return self::$_controllers;
    }

    /**
     * Type of the application
     *
     * @return string 'api' or 'web'
     */
    public function appType(): string
    {
        return $this->_appType;
    }

    /**
     * Constructor
     *
     * @param Engine $engine An engine instance 
     */
    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;

        $ctrlName = get_class($this);
        if (!in_array($ctrlName, self::$_controllers)) {
            self::$_controllers[] = $ctrlName;
        }

        $this->_appType = isset($this->engine()->form()->mode)
            ? $this->engine()->form()->mode
            : Env::get('APP_TYPE');
        if ($this->_appType != 'api') {
            $this->_appType = 'web';
        }
    }

    /**
     * Before calling a controller, route engine call this method
     *
     * @param array $callback The calling callback
     * @return void
     */
    public function __calling(array $callback): void
    {
        // apply all protections to callback
        $this->engine()->protect()->apply($callback);
    }

    /**
     * After called a controller, route engine call this method
     *
     * @param array $callback The called callback
     * @return void
     */
    public function __called(array $callback): void
    {
    }

    /**
     * Application is an API
     *
     * @return boolean true, it's an API, else, false
     */
    public function isApi(): bool
    {
        return ($this->_appType == 'api');
    }

    /**
     * Application is a web app
     *
     * @return boolean true, it's a web app, else, false
     */
    public function isWeb(): bool
    {
        return ($this->_appType == 'web');
    }

    /**
     * Get the uri of an added route by her name
     *
     * @param string $name Name of the route
     * @param array $params Params to fill, optionnal
     * @param boolean $full true, full url returned, else false
     * @return string
     */
    public function getRoute(string $name, array $params = [], bool $full = true): string
    {
        return $this->engine()->route()->get($name, $params, $full);
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
        return $this->engine()->route()->call($name, $params);
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
        $this->engine()->route()->redirect($name, $afterDelay);
    }

    /**
     * Render web page from template name with params
     *
     * @param string $name Template name
     * @param array $params Template params
     * @return void
     */
    public function render(array $params = [])
    {
        $templateName = '';

        $hasApi = false;
        $apiParams = [];

        $hasWeb = false;
        $webParams = [];

        if (array_key_exists('api', $params)) {
            $hasApi = true;
            $apiParams = is_array($params['api']) ? $params['api'] : [];
            unset($params['api']);
        }

        if (count($params) > 0) {
            $hasWeb = true;
            $templateName = array_keys($params)[0];
            $webParams = is_array($params[$templateName]) ? $params[$templateName] : [];
        }

        if ($this->_appType == 'api' && $hasApi) {
            sendJSON($apiParams);
            return;
        }

        if ($this->_appType == 'web' && $hasWeb) {
            $this->engine()->template()->render($templateName, $webParams);
            return;
        }
    }

    /**
     * Call a HTTP error code
     *
     * @param integer $code The HTTP error code
     * @return void
     */
    public function callError(int $code, array $params = []): void
    {
        $this->call(strval($code), $params);
    }

    /**
     * Call a HTTP error code
     *
     * @param integer $code The HTTP error code
     * @return void
     */
    public function redirectError(int $code, int $afterDelay = 0): void
    {
        $this->redirect(strval($code), $afterDelay);
    }
}
