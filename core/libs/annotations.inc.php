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
        'scanEnums' => '/@enum[\s\t]+(\w+):(\w+)[\s\t]+"(.*)"/',
        'scanControllers' => [
            '/@route[\s\t]+(\w+)[\s\t]+(web|api|web[\s\t]*,[\s\t]*api|api[\s\t]*,[\s\t]*web):(GET|POST|PUT|DELETE)[\s\t]+"(.+)"/' => [
                'engine' => 'route',
                'computeMethod' => 'computeAnnotation'
            ],
            '/@protect[\s\t]+flood[\s\t]+([0-9]+(\.[0-9]+)?)[\s\t]*s/' => [
                'engine' => 'protect',
                'computeMethod' => 'computeFloodAnnotation'
            ],
        ],
    ];

    /**
     * Scan look for php annotations
     */
    public static function scan(Engine $engine): void
    {
        foreach (self::SCAN_FOR as $method => $rules) {
            call_user_func('\\Core\\Libs\\Annotations::' . $method, $engine);
        }
    }

    /**
     * Scan look for php enums annotations
     *
     * @param Engine $engine
     * @return void
     */
    public static function scanEnums(Engine $engine): void
    {
        $enums = [];

        $ctrlFiles = globr(__DIR__ . '/../', '/^.*.inc.php$/', true);
        foreach ($ctrlFiles as $file) {
            try {
                $cls = new ReflectionClass(filepathToClass($file));

                $doc = $cls->getDocComment();
                if ($doc === false) {
                    continue;
                }

                $regex = self::SCAN_FOR[__FUNCTION__];
                if (preg_match_all($regex, $doc, $matches)) {
                    if (!is_array($matches)) {
                        continue;
                    }

                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $name = trim($matches[1][$i]);
                        $key = trim($matches[2][$i]);

                        $name = trim($name);

                        $key = trim($key);
                        if (is_numeric($key)) {
                            settype($key, 'integer');
                        }

                        $value = $matches[3][$i];

                        if (!array_key_exists($name, $enums)) {
                            if ($key != '') {
                                $enums[$name] = [$key => $value];
                            } else {
                                $enums[$name] = $value;
                            }
                        } else {
                            if (
                                is_array($enums[$name])
                                && $key != ''
                            ) {
                                $enums[$name][$key] = $value;
                            } elseif (
                                is_array($enums[$name])
                                && $key == ''
                            ) {
                                $enums[$name][] = $value;
                            } else {
                                $enums[$name] = $value;
                            }
                        }
                    }
                }
            } catch (Exception $ex) {
                continue;
            }
        }

        $GLOBALS['enums'] = (object)$enums;
    }

    /**
     * Scan look for php controllers annotations
     *
     * @param Engine $engine
     * @return void
     */
    public static function scanControllers(Engine $engine): void
    {
        if (filter_var(Env::get('APP_USECACHE', 'false'), FILTER_VALIDATE_BOOLEAN)) {
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

                    foreach (self::SCAN_FOR[__FUNCTION__] as $regex => $callback) {
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