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

namespace Core;

use Core\Libs\Annotations;
use Core\Libs\Env;
use Core\Libs\Database;
use Core\Libs\Form;
use Core\Libs\IDB;
use Core\Libs\Route;
use Core\Libs\Settings;
use Core\Libs\Template;
use Core\Libs\Translation;
use Core\Models\User;

use function Core\Libs\auth;
use function Core\Libs\autoImport;
use function Core\Libs\initSession;
use function Core\Libs\startSession;

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
    private ?User $_user;

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
     * Current user
     *
     * @return User
     */
    public function user(): User
    {
        return $this->_user;
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

        // init and start session manager
        initSession();
        startSession();

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

        // scan all controller's annotations
        Annotations::scan($this);

        // load the current user
        $this->_user = auth($this);
        if (is_null($this->_user)) {
            $this->_user = new User($this);
        }
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        Settings::save();
    }
}
