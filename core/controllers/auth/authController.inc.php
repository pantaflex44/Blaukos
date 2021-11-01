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

use function Core\Libs\auth;
use function Core\Libs\logError;
use function Core\Libs\sendJSON;

/**
 * Controllers group to manage home/index page
 */
class AuthController extends Controller
{

    /**
     * Controller: login
     * Only for web app, render a login form to authentificate
     *
     * @route 'login' 'web' 'GET' '/login'
     * @return void
     */
    public function login()
    {
        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->engine()->route()->redirect('404');
        }

        if (!$this->engine()->user()->isGuest()) {
            $this->engine()->route()->call('400');
        }

        $formId = $this->engine()->form()->createRandomFormId();
        $csrfField = $this->engine()->form()->csrfHiddenInput($formId);

        $this->engine()->template()->render(
            'auth/login',
            [
                'formId' => $formId,
                'formMethod' => 'POST',
                'csrfField' => $csrfField,
                'action' => 'authentificate',
            ]
        );
    }

    /**
     * Controller: logout
     *
     * @route 'logout' 'web,api' 'GET' '/logout'
     * @return void
     */
    public function logout()
    {
        if ($this->engine()->user()->isGuest()) {
            $this->engine()->route()->call('400');
        }

        $this->engine()->user()->clearToken();

        if (Env::get('APP_TYPE') == 'api') {
            // it's an api
            sendJSON(['action' => 'home']);
        }

        if (Env::get('APP_TYPE') == 'web') {
            // it's a web app
            if (isset($_SESSION['JWT_TOKEN'])) {
                unset($_SESSION['JWT_TOKEN']);
            }

            $this->engine()->route()->redirect('home');
        }
    }

    /**
     * Controller: csrf
     * Retreive new CSRF token
     * 
     * @route 'csrf' 'api' 'GET' '/authentificate/csrf/{id:string}'
     * @return void
     */
    public function csrf(string $id)
    {
        sendJSON($this->engine()->form()->csrfCreate($id));
    }

    /**
     * Controller: authentificate
     * Authentificate an user from 'username' and 'password' form post value
     * 
     * In API mode, make before a get request to /authentificate/csrf/{id:string},
     * where {id:string} is the ID to create the CSRF token key.
     * eg:
     *  request      = GET /authentificate/csrf/mykey256
     *  json result  = {"csrfKey":"mykey256_csrf","csrfToken":"88faac6a39c052fc98a0b680cdfac48e","http":200}
     *  next request = POST /authentificate (with correct post params, csrf token included)
     * 
     * @route 'authentificate' 'web,api' 'POST' '/authentificate'
     * @return void
     */
    public function authentificate()
    {
        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->engine()->route()->redirect('404');
        }

        if (!$this->engine()->form()->csrfVerify()) {
            $this->engine()->route()->call('400');
        }

        $username = isset($this->engine()->form()->username)
            ? $this->engine()->form()->username
            : null;
        $password = isset($this->engine()->form()->password)
            ? $this->engine()->form()->password
            : null;
        if (is_null($username) || is_null($password)) {
            $this->engine()->route()->call('400');
        }

        $username = filter_var(trim($username), FILTER_SANITIZE_STRING);
        $password = filter_var(trim($password), FILTER_SANITIZE_STRING);

        $user = new User($this->engine());
        $userId = $user->login($username, $password);
        if (is_null($userId)) {
            $this->engine()->route()->call('401');
        }

        if (is_null($user->fromId($userId))) {
            logError(
                sprintf(
                    'Authentificate: Unable to load user id:%d.',
                    $userId
                ),
                __FILE__,
                __LINE__
            );

            $this->engine()->route()->call('500');
        }

        if ($user->active != 1) {
            $this->engine()->route()->call('403');
        }

        $token = $user->updateToken();
        if (is_null($token)) {
            logError(
                sprintf(
                    'Authentificate: Token not updated for user id:%d.',
                    $userId
                ),
                __FILE__,
                __LINE__
            );

            $this->engine()->route()->call('500');
        }

        // share the cookie for future use
        header("Authorization: Bearer $token");     # http authorization header for api
        $_SESSION['JWT_TOKEN'] = $token;            # session cookie for web app

        if (Env::get('APP_TYPE') == 'web') {
            // it's a web app
            $this->engine()->template()->render(
                'auth/authentificated',
                [
                    'user'      => $user,
                    'action'    => 'dashboard',
                ]
            );
        }

        if (Env::get('APP_TYPE') == 'api') {
            // it's an api
            sendJSON(
                [
                    'userId'    => $userId,
                ],
                $token
            );
        }
    }
}
