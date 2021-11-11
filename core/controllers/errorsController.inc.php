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
     * Raise an HTTP error
     *
     * @param integer $code HTTP error code
     * @param string $message HTTP error message
     * @param string $info Custum message
     * @return void
     */
    private function _error(int $code, string $message, string $info)
    {
        logHttpError(debug_backtrace(), $code, __FILE__, __LINE__);

        $this->render([
            'httpError'     => [
                'code'      => $code,
                'message'   => $message,
                'info'      => $info,
            ]
        ]);

        abort($code, $this->appType());
    }

    /**
     * Controller: error 500
     *
     * @route 500 web,api:GET "/500"
     * @return void
     */
    public function error500()
    {
        $this->_error(
            500,
            _("Erreur critique. Veuillez réessayer plus-tard."),
            _("Houston, on a un problème!")
        );
    }

    /**
     * Controller: error 429
     *
     * @route 429 web,api:GET "/429"
     * @return void
     */
    public function error429()
    {
        $this->_error(
            429,
            _("Nombre de requètes trop importantes."),
            _("Le flood, c'est mal!")
        );
    }

    /**
     * Controller: error 404
     *
     * @route 404 web,api:GET "/404"
     * @return void
     */
    public function error404()
    {
        $this->_error(
            404,
            _("Page introuvable."),
            _("Mais où vas-tu?")
        );
    }

    /**
     * Controller: error 403
     *
     * @route 403 web,api:GET "/403"
     * @return void
     */
    public function error403()
    {
        $this->_error(
            403,
            _("Accès refusé."),
            _("Papier d'identité s'il vous plait!"),
        );
    }

    /**
     * Controller: error 401
     *
     * @route 401 web,api:GET "/401"
     * @return void
     */
    public function error401()
    {
        $this->_error(
            401,
            _("Visiteur interdit. Veuillez vous identifier."),
            _("Dans 200m, au rond point, faites demi-tour."),
        );
    }

    /**
     * Controller: error 400
     *
     * @route 400 web,api:GET "/400"
     * @return void
     */
    public function error400()
    {
        $this->_error(
            400,
            _("Demande erronée."),
            _("Mais qu'as tu fait Maurice?"),
        );
    }
}
