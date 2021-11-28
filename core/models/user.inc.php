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
use Core\Libs\Env;
use Core\Libs\Tto;
use DateTimeImmutable;
use Exception;
use function Core\Libs\jwtToken;
use function Core\Libs\passwordCompare;
use function Core\Libs\secureToken;

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
 * @field resetLinkToken:string ""
 * @field resetLinkValidity:datetime "1970-01-01 00:00:00"
 *
 * @enum roleTitle:0 "Visiteur"
 * @enum roleTitle:1 "Abonné"
 * @enum roleTitle:99 "Administrateur"
 */
class User extends Tto
{

    /**
     * The constructor
     *
     * @param Engine $engine
     * @param int|null $id
     */
    public function __construct(Engine $engine, ?int $id = null)
    {
        parent::__construct($engine, $id);
    }

    /**
     * Authentificate an user by username and password
     *
     * @param string $username
     * @param string $password
     * @return User
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
        if ($this->isGuest()) {
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
     * Is it a guest user?
     *
     * @return boolean true, user is a guest user, else, false
     */
    public function isGuest(): bool
    {
        return (isset($this->id) && $this->id == -1);
    }

    /**
     * Clear the authorization token
     *
     * @return bool true, token cleared, else, false
     */
    public function clearToken(): bool
    {
        if ($this->isGuest()) {
            return false;
        }

        try {
            $this->token = '';

            return $this->update();
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Set new reset link
     *
     * @return User|null
     */
    public function setResetLink(): ?User
    {
        if ($this->id === -1) {
            return null;
        }

        try {
            $this->resetLinkToken = secureToken();
            $this->resetLinkValidity = (new DateTimeImmutable())
                ->modify('+' . Env::get('RESET_LINK_VALIDITY', '60 minutes'));

            if (!$this->update()) {
                return null;
            }

            return $this;
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Clear last reset link
     *
     * @return User|null
     */
    public function clearResetLink(): ?User
    {
        if ($this->id === -1) {
            return null;
        }

        try {
            $this->resetLinkToken = '-';
            $this->resetLinkValidity = (new DateTimeImmutable());

            if (!$this->update()) {
                return null;
            }

            return $this;
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Is it a guest user?
     *
     * @return boolean true, user is a logged user, else, false
     */
    public function isLogged(): bool
    {
        return (isset($this->id) && $this->id > -1);
    }
}