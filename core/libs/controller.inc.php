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
    }
}
