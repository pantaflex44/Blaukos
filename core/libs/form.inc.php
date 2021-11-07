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
 * Manage forms and post datas
 */
class Form
{

    private Engine $_engine;
    private array $_gets = [];
    private array $_posts = [];

    /**
     * Return form data by her key name
     *
     * @param string $name The key name of form data
     * @return mixed The data
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->_gets)) {
            return $this->_gets[$name];
        }

        if (array_key_exists($name, $this->_posts)) {
            return $this->_posts[$name];
        }

        return null;
    }

    /**
     * Disable data setter
     */
    public function __set(string $name, $value)
    {
    }

    /**
     * Data exists
     *
     * @param string $name The key name of form data
     * @return boolean true, data exists, else, false
     */
    public function __isset(string $name)
    {
        return array_key_exists($name, $this->_gets) || array_key_exists($name, $this->_posts);
    }

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine  = $engine;

        if (isset($_GET) && is_array($_GET)) {
            foreach ($_GET as $key => $value) {
                $this->_gets[$key] = $value;
            }
        }

        if (isset($_POST) && is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                $this->_posts[$key] = $value;
            }
        }
    }

    /**
     * Return all params/datas
     *
     * @return array All params/datas
     */
    public function all(): array
    {
        return array_merge(
            $this->_gets,
            $this->_posts
        );
    }

    /**
     * Create a random form Id
     *
     * @return string The form Id
     */
    public function createRandomFormId(): string
    {
        return bin2hex(random_bytes(10));
    }

    /**
     * Create a CSRF token
     *
     * @param string $formId An unique form Id
     * @return array The token
     */
    public function csrfCreate(string $formId = ''): array
    {
        if ($formId == '') {
            $formId = $this->createRandomFormId();
        }

        $key = sprintf('%s_csrf', $formId);
        $csrfToken = md5(uniqid(mt_rand(), true));

        $_SESSION[$key] = $csrfToken;

        return ['csrfKey' => $key, 'csrfToken' => $csrfToken];
    }

    /**
     * Verify CSRF token
     *
     * @return boolean true, it's a valid csrf token, else, false
     */
    public function csrfVerify(): bool
    {
        $keys = array_keys($_POST);
        $keys = array_values(array_filter($keys, function (string $key) {
            if (endsWith($key, '_csrf')) {
                return $key;
            }
        }));
        if (count($keys) != 1) {
            header('location: /400');
            exit;
        }

        $key = $keys[0];
        $csrfToken = $_POST[$key];

        if (!isset($_SESSION[$key])) {
            return false;
        }

        $valid = ($_SESSION[$key] == $csrfToken);

        $_SESSION[$key] = '';
        unset($_SESSION[$key]);

        return $valid;
    }

    /**
     * Return a CSRF form field
     *
     * @param string $formId An unique form Id
     * @return string The hidden input
     */
    public function csrfHiddenInput(string $formId = ''): string
    {
        $csrf = $this->csrfCreate($formId);
        return sprintf(
            '<input type="hidden" id="csrf_field" name="%s" value="%s">',
            $csrf['csrfKey'],
            $csrf['csrfToken']
        );
    }
}
