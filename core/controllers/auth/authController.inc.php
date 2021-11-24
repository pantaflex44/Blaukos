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
use PHPMailer\PHPMailer\PHPMailer;

use function Core\Libs\logError;

/**
 * Controllers group to manage home/index page
 */
class AuthController extends Controller
{

    /**
     * Controller: login
     * Only for web app, render a login form to authentificate
     *
     * @route login web:GET "/login"
     * 
     * @protect flood 2s
     *
     * @user allowed "type:guest"
     * @user denied "type:logged"
     * 
     * @return void
     */
    public function login()
    {
        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->redirectError(404);
        }

        $user = $this->engine()->user();
        $form = $this->engine()->form();

        if (!$user->isGuest()) {
            $this->redirectError(400);
        }

        $formLoginId = 'login';
        $formPasswordLostId = 'passwordlost';

        $this->render([
            'auth/login'                    => [
                'formIdLogin'               => $formLoginId,
                'formMethodLogin'           => 'POST',
                'csrfFieldLogin'            => $form->csrfHiddenInput($formLoginId),
                'actionLogin'               => $this->getRoute('authentificate'),

                'formIdPasswordLost'        => $formPasswordLostId,
                'formMethodPasswordLost'    => 'POST',
                'csrfFieldPasswordLost'     => $form->csrfHiddenInput($formPasswordLostId),
                'actionPasswordLost'        => $this->getRoute('passwordlost'),
            ]
        ]);
    }

    /**
     * Controller: logout
     *
     * @route logout web,api:GET "/logout"
     * 
     * @protect flood 5s
     * 
     * @return void
     */
    public function logout()
    {
        $user = $this->engine()->user();

        if ($user->isGuest()) {
            $this->callError(400);
        }

        $user->clearToken();
        if (isset($_SESSION['JWT_TOKEN'])) {
            unset($_SESSION['JWT_TOKEN']);
        }

        $this->redirect('home');
    }

    /**
     * Controller: csrf
     * Retreive new CSRF token
     *
     * @route randomcsrf web,api:POST "/authentificate/csrf" 
     * @route newcsrf web,api:POST "/authentificate/csrf/{id:string}"
     * 
     * @protect flood 5s
     * 
     * @return void
     */
    public function csrf(string $id = '')
    {
        $form = $this->engine()->form();

        $this->render([
            'api' => $form->csrfCreate($id),
        ]);
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
     * @route authentificate web,api:POST "/authentificate"
     * 
     * @protect flood 5s
     * 
     * @return void
     */
    public function authentificate()
    {
        $form = $this->engine()->form();

        $username = $form->username ?? null;
        $password = $form->password ?? null;

        // create and pass new CSRF Token in HTTP headers
        $csrfHeader = function (): void {
            $newCsrf = $this->engine()->form()->csrfCreate();
            $newCsrf = sprintf(
                'csrf-token: %s; %s',
                $newCsrf['csrfKey'],
                $newCsrf['csrfToken']
            );

            header($newCsrf, true);
        };

        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $csrfHeader();
            $this->redirectError(404);
        }

        if (!$form->csrfVerify()) {
            $csrfHeader();
            $this->callError(400);
        }

        if (is_null($username) || is_null($password)) {
            $csrfHeader();
            $this->callError(400);
        }

        $username = filter_var(trim($username), FILTER_SANITIZE_STRING);
        $password = filter_var(trim($password), FILTER_SANITIZE_STRING);

        $user = (new User($this->engine()))->login($username, $password);
        if (!$user->isLogged()) {
            $csrfHeader();
            $this->callError(401);
        }

        if ($user->active != 1) {
            $csrfHeader();
            $this->callError(403);
        }

        $token = $user->updateToken();
        if (is_null($token)) {
            logError(
                sprintf(
                    'Authentificate: Token not updated for user id:%d.',
                    $user->id
                ),
                __FILE__,
                __LINE__
            );

            $csrfHeader();
            $this->callError(500);
        }

        // share the cookie for future use
        header("Authorization: Bearer $token");     # http authorization header for api
        $_SESSION['JWT_TOKEN'] = $token;            # session cookie for web app

        $this->render([
            'api'                   => [
                'userId'            => $user->id,
                'token'             => $token,
            ],
            'auth/authentificated'  => [
                'user'              => $user,
                'action'            => $this->getRoute('dashboard'),
            ]
        ]);
    }

    /**
     * Controller: passwordlost
     * Send a reset password link
     *
     * @route passwordlost web,api:POST "/passwordlost" 
     * 
     * @protect flood 2s
     * 
     * @return void
     */
    public function passwordlost()
    {
        $user = $this->engine()->user();
        $form = $this->engine()->form();

        if ($user->isLogged()) {
            $this->callError(400);
        }

        $username = isset($form->username)
            ? filter_var($form->username, FILTER_SANITIZE_STRING)
            : null;

        $wantedUser = (new User($this->engine()))
            ->where('username', '=', $username, true)
            ->take(1)
            ->get();

        if (is_null($wantedUser) || $wantedUser->username !== $username) {
            $errorMessage = _("Nom d'utilisateur incorrect.");
            $this->render([
                'api'                => ['error' => $errorMessage],
                'auth/passwordsent'  => ['error' => $errorMessage]
            ]);
            return;
        }

        if (is_null($wantedUser->setResetLink())) {
            $errorMessage = _("Impossible de créer le lien de réinitialisation du mot de passe.");
            $this->render([
                'api'                => ['error' => $errorMessage],
                'auth/passwordsent'  => ['error' => $errorMessage]
            ]);
            return;
        }

        $link = $this->getRoute('passwordreset', [
            'userId'    => $wantedUser->id,
            'token'     => $wantedUser->resetLinkToken,
        ]);

        $to = $wantedUser->email;
        $sended = $this->engine()->sendMail(
            $to,
            _("Lien pour la réinitialisation du mot de passe de votre compte."),
            'passwordLostResetLink',
            [
                'user' => $wantedUser,
                'link' => $link,
            ]
        );
        if (!$sended) {
            $errorMessage = _("Impossible d'envoyer l'email contenant le lien de réinitialisation.");
            $this->render([
                'api'                => ['error' => $errorMessage],
                'auth/passwordsent'  => ['error' => $errorMessage]
            ]);
            return;
        }

        $this->render([
            'api'               => ['wantedUserId'  => $wantedUser->id],
            'auth/passwordsent' => ['wantedUser'    => $wantedUser]
        ]);
    }

    /**
     * Controller: passwordreset
     * Reset the password
     *
     * @route passwordreset web:GET "/passwordreset/{userId:integer}/{token:string}" 
     * @route passwordreset api:POST "/passwordreset/{userId:integer}/{token:string}" 
     * 
     * @protect flood 2s
     * 
     * @return void
     */
    public function passwordreset(int $userID, string $token)
    {
        die(sprintf('%s - %s', $userID, $token));
    }
}
