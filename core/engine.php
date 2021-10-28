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

namespace Core;

use Core\Libs\Env;
use Core\Libs\Database;
use Core\Libs\Form;
use Core\Libs\IDB;
use Core\Libs\Route;
use Core\Libs\Settings;
use Core\Libs\Template;
use Core\Libs\Translation;

use function Core\Libs\autoImport;

/**
 * Import utilities
 */
require_once __DIR__ . '/libs/utilities.inc.php';

/**
 * Composer autoloader
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Twig custom extensions
 */
require_once __DIR__ . '/libs/twig/extensions.inc.php';

/**
 * Autoload all custom objects files by their class names
 */
autoImport();

/**
 * Main public entry point
 */
class Engine
{

    private ?IDB $_db = null;
    private Route $_route;
    private Form $_form;
    private Template $_template;
    private Translation $_translation;

    /**
     * Return the database manager
     *
     * @return IDB|null The database manager or null 
     */
    public function db(): ?IDB
    {
        return $this->_db;
    }

    /**
     * Routes manager
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->_route;
    }

    /**
     * Form manager
     *
     * @return Form
     */
    public function form(): Form
    {
        return $this->_form;
    }

    /**
     * Form manager
     *
     * @return Form
     */
    public function template(): Template
    {
        return $this->_template;
    }

    /**
     * Translation manager
     *
     * @return Translation
     */
    public function tr(): Translation
    {
        return $this->_translation;
    }

    /**
     * The constructor
     */
    public function __construct()
    {
        // load .env files
        Env::load();

        // load settings
        Settings::load();

        // load the database manager
        $this->_db = Database::instance();

        // load translation manager
        $this->_translation = new Translation($this);

        // give access to Route manager and load default routes
        $this->_route = new Route($this);

        // give access to Form manager
        $this->_form = new Form($this);

        // load the template manager
        $this->_template = new Template($this);
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        Settings::save();
    }
}
