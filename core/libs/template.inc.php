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
use Core\Libs\Twig\CustomTwigExtensions;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Manage templates
 */
class Template
{
    private Engine $_engine;
    private ?Environment $_twig = null;

    /**
     * Template manager
     *
     * @return mixed
     */
    public function twig(): ?Environment
    {
        return $this->_twig;
    }

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine  = $engine;

        // load the template manager if is a web app
        if (Env::get('APP_TYPE') == 'web') {
            $twig = new FilesystemLoader(__DIR__ . '/../views');
            $this->_twig = new Environment($twig, [
                'cache' => (Env::get('APP_USECACHE', 'true') == 'true' ? true : false)
                    ? __DIR__ . '/../views/cache'
                    : false,
                'debug' => (Env::get('APP_DEBUG', 'true') == 'true' ? true : false),
                'charset' => 'utf-8',
            ]);
            $this->_twig->addExtension(new CustomTwigExtensions($this->_engine));
        }
    }

    /**
     * Render web page from template name with params
     *
     * @param string $name Template name
     * @param array $params Template params
     * @return void
     */
    public function render(string $name, array $params = [])
    {
        // if not a web app, return
        if (Env::get('APP_TYPE') != 'web') {
            return;
        }

        $names = explode('/', $name);
        $templateName = array_pop($names);

        $enums = isset($GLOBALS['enums']) ? $GLOBALS['enums'] : null;

        $htmldir = $this->_engine->user()->isLogged()
            ? $this->_engine->user()->htmldir
            : Settings::get('html_dir', 'ltr');

        // else, load and show template page
        $content = $this->twig()->render(
            $name . '.html.twig',
            array_merge(
                [
                    'enums'             => $enums,
                    'user'              => $this->_engine->user(),
                    'templateName'      => $templateName,
                    'locale'            => $this->_engine->tr()->getCurrent(),
                    'lang'              => $this->_engine->tr()->getLanguageCode(),
                    'dir'               => $htmldir,
                    'charset'           => Settings::get('charset', 'UTF-8'),
                ],
                $params
            )
        );

        echo $content;
    }
}
