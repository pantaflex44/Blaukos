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

/**
 * Manage forms and post datas
 */
class Form
{

    private Engine $_engine;

    private array $_datas = [];

    /**
     * Form post datas
     *
     * @return void
     */
    public function datas()
    {
        return $this->_datas;
    }

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine  = $engine;

        if (isset($_POST) && is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                $key = trim(filter_var($key, FILTER_SANITIZE_STRING));
                $value = trim(filter_var($value, FILTER_SANITIZE_STRING));

                $this->_datas[$key] = $value;
            }
        }
    }
}
