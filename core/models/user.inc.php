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

namespace Core\Models;

use Core\Engine;
use Core\Libs\Tto;
use PDO;

use function Core\Libs\jwtToken;
use function Core\Libs\passwordCompare;

/**
 * An user object
 */
class User extends Tto
{
    /**
     * The constructor
     * 
     * @param Engine $engine
     */
    public function __construct(Engine $engine, ?int $id = null)
    {
        $default = [
            'id'            => -1,
            'token'         => '',
            'active'        => 1,
            'username'      => '',
            'password'      => '',
            'displayName'   => _("Invité"),
            'email'         => '',
            'createdAt'     => '1970-01-01 00:00:00',
            'role'          => 0,
        ];

        parent::__construct($engine, 'users', $default, $id);
    }

    /**
     * Is it a guest user?
     *
     * @return boolean true, user is a guest user, else, false
     */
    public function isGuest(): bool
    {
        return ($this->id == -1);
    }

    /**
     * Authentificate an user by username and password
     *
     * @param string $username
     * @param string $password
     * @return Tto
     */
    public function login(string $username, string $password): ?int
    {
        $result = $this->fetch(
            "SELECT id, password FROM :tableName WHERE BINARY username = :username LIMIT 1",
            [
                ['username', $username, PDO::PARAM_STR],
            ]
        );

        if (is_null($result) || !is_array($result) || count($result) == 0) {
            return null;
        }

        if (!passwordCompare($password, $result['password'])) {
            return null;
        }

        return $result['id'];
    }

    /**
     * Create and update the authorization token
     *
     * @return string|mixed Http Authorization token or null on error
     */
    public function updateToken(?string $token = null): ?string
    {
        if (!isset($this->id) || $this->id == -1) {
            return null;
        }

        if (is_null($token)) {
            $token = jwtToken($this->id);
        }

        $result = $this->execute(
            "UPDATE :tableName SET token = :token WHERE BINARY id = :id",
            [
                ['token', $token, PDO::PARAM_STR],
                ['id', $this->id, PDO::PARAM_INT],
            ]
        );

        if (is_null($result) || $result == 0) {
            return null;
        }

        $this->fromId($this->id);
        return $token;
    }

    /**
     * Clear the authorization token
     *
     * @return bool true, token cleared, else, false
     */
    public function clearToken(): bool
    {
        if (!isset($this->id) || $this->id == -1) {
            return false;
        }

        $result = $this->execute(
            "UPDATE :tableName SET token = :token WHERE BINARY id = :id",
            [
                ['token', '', PDO::PARAM_STR],
                ['id', $this->id, PDO::PARAM_INT],
            ]
        );

        if (is_null($result) || $result == 0) {
            return false;
        }

        $this->fromId($this->id);
        return true;
    }
}
