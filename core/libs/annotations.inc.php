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
use ReflectionClass;
use ReflectionMethod;

/**
 * Manage PHP annotations
 */
class Annotations
{

    /**
     * Scan rules
     */
    private const SCAN_FOR = [
        '/@route[\s\t]+\'(.+)\'[\s\t]+\'(.+)\'[\s\t]+\'(.+)\'/' => [
            'engine' => 'route',
            'computeMethod' => 'computeAnnotation'
        ],
    ];

    /**
     * Scan look for php annotations
     *
     * @return void
     */
    public static function scan(Engine $engine)
    {
        if (Env::get('APP_USECACHE', 'false') == 'true') {
            return;
        }

        $ctrlFiles = globr(__DIR__ . '/../', '/^.*Controller.inc.php$/', true);

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
                    if ($doc === false) {
                        continue;
                    }

                    foreach (self::SCAN_FOR as $regex => $callback) {
                        if (preg_match_all($regex, $doc, $matches)) {
                            if (!is_array($matches)) {
                                continue;
                            }

                            $cls = call_user_func(
                                [
                                    $engine,
                                    $callback['engine']
                                ]
                            );

                            call_user_func_array(
                                [
                                    $cls,
                                    $callback['computeMethod']
                                ],
                                [
                                    $className,
                                    $method->name,
                                    $matches,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}
