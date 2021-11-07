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
use DateTimeImmutable;
use Exception;

use function Core\Libs\jwtToken;
use function Core\Libs\passwordCompare;

/**
 * An user table
 * 
 * @table users
 * 
 * @field id:integer "-1"
 * @field token:string ""
 * @field active:integer "1"
 * @field username:string ""
 * @field password:string ""
 * @field displayName:string "visiteur"
 * @field email:string ""
 * @field createdAt:datetime "1970-01-01 00:00:00"
 * @field lastLoggedAt:datetime "1970-01-01 00:00:00"
 * @field role:integer "0"
 * @field locale:string "fr_FR"
 * @field htmldir:string "ltr"
 * 
 * @enum roleTitle:0 "Visiteur"
 * @enum roleTitle:1 "Abonné"
 * @enum roleTitle:2 "Gestionnaire"
 * @enum roleTitle:98 "Administrateur"
 * @enum roleTitle:99 "Super-Administrateur"
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
        parent::__construct($engine, $id);
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
     * Is it a guest user?
     *
     * @return boolean true, user is a logged user, else, false
     */
    public function isLogged(): bool
    {
        return ($this->id > -1);
    }

    /**
     * Authentificate an user by username and password
     *
     * @param string $username
     * @param string $password
     * @return Tto
     */
    public function login(string $username, string $password): User
    {
        try {
            $this->where('username', '=', $username, true)
                ->get();

            if (!passwordCompare($password, $this->password)) {
                $this->reset();
            }

            $this->lastLoggedAt = new DateTimeImmutable();
            $this->update();
        } catch (Exception $ex) {
            $this->reset();
        }

        return $this;
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

        try {
            $this->token = $token;
            if (!$this->update()) {
                return null;
            }

            return $this->token;
        } catch (Exception $ex) {
            return null;
        }
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

        try {
            $this->token = '';

            return $this->update();
        } catch (Exception $ex) {
            return false;
        }
    }
}
