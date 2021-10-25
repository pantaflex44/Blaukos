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

namespace Core\Controllers;

use Core\Libs\Controller;

use function Core\Libs\abort;

/**
 * Controllers group to manage errors page
 */
class ErrorsController extends Controller
{

    /**
     * Controller: error 404
     *
     * @route '404' 'GET' '/404'
     * @return void
     */
    public function error404()
    {
        $this->engine()->render('404');

        abort(404);
    }
}
