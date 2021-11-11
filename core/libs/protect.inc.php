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
 * Protect application.
 * - Antiflood requests.
 */
class Protect
{
    private const FILE = __DIR__ . '/../datas/protects.datas';

    private Engine $_engine;

    private string $_ip;
    private array $_protects = [
        'flood'         => [],
    ];

    /**
     * Compute annotation found by Annotation system
     *
     * @param string $className
     * @param string $methodName
     * @param array $matches
     * @return void
     */
    public function computeFloodAnnotation(string $className, string $methodName, array $matches): void
    {
        if (count($matches) < 2) {
            return;
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            $delaiStr = $matches[1][$i];

            $LocaleInfo = localeconv();
            $delaiStr = str_replace($LocaleInfo['mon_thousands_sep'], '', $delaiStr);
            $delaiStr = str_replace($LocaleInfo['mon_decimal_point'], '.', $delaiStr);

            $delai = floatval($delaiStr);

            $key = $className . '\\' . $methodName;
            $this->_protects['flood'][$key] = $delai;
        }

        $this->_save();
    }

    /**
     * Save protections for caching
     *
     * @return void
     */
    private function _save()
    {
        $srz = serialize($this->_protects);
        @file_put_contents(self::FILE, $srz);
    }

    /**
     * Load cached protections
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

            $this->_protects = unserialize($srz);

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Apply flood protection
     *
     * @param array $rules Controller rules
     * @return void
     */
    private function _applyFlood(string $caller, array $rules): void
    {
        if (!array_key_exists($caller, $rules)) {
            return;
        }

        $delay = $rules[$caller];
        $key = $this->_ip . '@' . $caller;
        $now = microtime(true);

        $lastTime = array_key_exists($key, $_SESSION['flood'][$this->_ip])
            ? $_SESSION['flood'][$this->_ip][$key]
            : null;

        if (!is_null($lastTime)) {
            if ($lastTime + $delay > $now) {
                logError(
                    sprintf(
                        'Flood protection: %s call again the controller %s, with delay: %ds',
                        $this->_ip,
                        $caller,
                        $delay
                    ),
                    __FILE__,
                    __LINE__
                );

                $this->_engine->route()->call('429');
            }
        }

        $_SESSION['flood'][$this->_ip][$key] = $now;
    }

    /**
     * The constructor
     *
     * @param Engine $engine
     */
    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;
        $this->_ip = realIp();

        $this->_load();

        if (!array_key_exists('flood', $_SESSION)) {
            $_SESSION['flood'] = [];
        }
        if (!array_key_exists($this->_ip, $_SESSION['flood'])) {
            $_SESSION['flood'][$this->_ip] = [];
        }
    }

    /**
     * Apply all protections
     *
     * @return void
     */
    public function apply(?array $callback = null)
    {
        if (is_null($callback) || count($callback) != 2) {
            $trace = debug_backtrace()[1];
            if (!$trace) {
                return;
            }

            $callerClass = isset($trace['class']) ? '\\' . $trace['class'] : '';
            $callerFunction = isset($trace['function']) ? $trace['function'] : '';
        } else {
            $callerClass = is_null($callback[0]) ? '' : get_class($callback[0]);
            $callerFunction = $callback[1];
        }

        $caller = $callerClass . '\\' . $callerFunction;
        if (!startsWith($caller, '\\')) {
            $caller = '\\' . $caller;
        }

        foreach ($this->_protects as $type => $rules) {
            $call = '_apply' . ucfirst($type);
            $this->$call($caller, $rules);
        }
    }
}
