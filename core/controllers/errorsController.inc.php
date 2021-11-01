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

use function Core\Libs\abort;
use function Core\Libs\logError;
use function Core\Libs\logHttpError;

/**
 * Controllers group to manage errors page
 */
class ErrorsController extends Controller
{

    /**
     * Controller: error 500
     *
     * @route '500' 'web,api' 'GET' '/500'
     * @return void
     */
    public function error500()
    {
        logHttpError(debug_backtrace(), 500, __FILE__, __LINE__);

        $this->engine()->template()->render(
            'httpError',
            [
                'code' => 500,
                'message' => _("erreur serveur"),
                'info' => _("Houston, on a un problème!"),
            ]
        );

        abort(500);
    }

    /**
     * Controller: error 404
     *
     * @route '404' 'web,api' 'GET' '/404'
     * @return void
     */
    public function error404()
    {
        logHttpError(debug_backtrace(), 404, __FILE__, __LINE__);

        $this->engine()->template()->render(
            'httpError',
            [
                'code' => 404,
                'message' => _("page non trouvée"),
                'info' => _("Mais où vas-tu?"),
            ]
        );

        abort(404);
    }

    /**
     * Controller: error 403
     *
     * @route '403' 'web,api' 'GET' '/403'
     * @return void
     */
    public function error403()
    {
        logHttpError(debug_backtrace(), 403, __FILE__, __LINE__);

        $this->engine()->template()->render(
            'httpError',
            [
                'code' => 403,
                'message' => _("accès refusé"),
                'info' => _("Papier d'identité s'il vous plait!"),
            ]
        );

        abort(403);
    }

    /**
     * Controller: error 401
     *
     * @route '401' 'web,api' 'GET' '/401'
     * @return void
     */
    public function error401()
    {
        logHttpError(debug_backtrace(), 401, __FILE__, __LINE__);

        $this->engine()->template()->render(
            'httpError',
            [
                'code' => 401,
                'message' => _("utilisateur non authentifié"),
                'info' => _("Dans 200m, au rond point, faites demi-tour."),
            ]
        );

        abort(401);
    }

    /**
     * Controller: error 400
     *
     * @route '400' 'web,api' 'GET' '/400'
     * @return void
     */
    public function error400()
    {
        logHttpError(debug_backtrace(), 400, __FILE__, __LINE__);

        $this->engine()->template()->render(
            'httpError',
            [
                'code' => 400,
                'message' => _("requète erronée"),
                'info' => _("Mais qu'as tu fait Maurice?"),
            ]
        );

        abort(400);
    }
}
