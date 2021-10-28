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

        // else, load and show template page
        $content = $this->twig()->render(
            $name . '.twig',
            array_merge(
                [
                    'templateName' => $name,
                    'locale' => $this->_engine->tr()->getCurrent(),
                    'lang' => $this->_engine->tr()->getLanguageCode(),
                    'dir' => Settings::get('html_dir', 'ltr'),
                    'charset' => Settings::get('charset', 'UTF-8'),
                ],
                $params
            )
        );

        echo $content;
    }
}
