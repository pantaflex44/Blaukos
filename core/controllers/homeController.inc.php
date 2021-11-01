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

namespace Core\Controllers;

use Core\Libs\Controller;
use Core\Libs\Env;
use Core\Models\User;
use PDO;

use function Core\Libs\auth;
use function Core\Libs\sendJSON;

/**
 * Controllers group to manage home/index page
 */
class HomeController extends Controller
{

    /**
     * Controller: index
     *
     * @route 'home' 'web' 'GET' '/'
     * @return void
     */
    public function index()
    {
        if (Env::get('APP_TYPE') == 'api') {
            // it's an api
            $this->engine()->route()->call('404');
        }

        if (Env::get('APP_TYPE') == 'web') {
            // it's a web app
            $this->engine()->template()->render('index');
        }
    }
}
