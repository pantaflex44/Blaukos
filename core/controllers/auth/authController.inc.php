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
use DateTimeImmutable;
use DateTimeZone;
use function Core\Libs\logError;
use function Core\Libs\password;
use function Core\Libs\passwordGoodStrength;

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
            $this->redirect('dashboard');
        }

        $formLoginId = 'login';
        $formPasswordLostId = 'passwordlost';

        $this->render([
            'auth/login' => [
                'formIdLogin' => $formLoginId,
                'formMethodLogin' => 'POST',
                'csrfFieldLogin' => $form->csrfHiddenInput($formLoginId),
                'actionLogin' => $this->getRoute('authentificate'),

                'formIdPasswordLost' => $formPasswordLostId,
                'formMethodPasswordLost' => 'POST',
                'csrfFieldPasswordLost' => $form->csrfHiddenInput($formPasswordLostId),
                'actionPasswordLost' => $this->getRoute('passwordlost'),
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
            $this->redirect('home');
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

        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->csrfHeader();
            $this->redirectError(404);
        }

        if (!$form->csrfVerify()) {
            $this->csrfHeader();
            $this->callError(400);
        }

        if (is_null($username) || is_null($password)) {
            $this->csrfHeader();
            $this->callError(400);
        }

        $username = filter_var(trim($username), FILTER_UNSAFE_RAW);
        $password = filter_var(trim($password), FILTER_UNSAFE_RAW);

        $user = (new User($this->engine()))->login($username, $password);
        if (!$user->isLogged()) {
            $this->csrfHeader();
            $this->callError(401);
        }

        if ($user->active != 1) {
            $this->csrfHeader();
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

            $this->csrfHeader();
            $this->callError(500);
        }

        // share the cookie for future use
        header("Authorization: Bearer $token");     # http authorization header for api
        $_SESSION['JWT_TOKEN'] = $token;            # session cookie for web app

        $this->render([
            'api' => [
                'userId' => $user->id,
                'userToken' => $token,
                'redirect' => $this->getRoute('dashboard'),
            ],
            'message' => [
                'title' => _("Authentification réussie"),
                'header' => _("Authentification réussie."),
                'message' => _("Vous avez désormais accès à votre espace."),
                'redirect' => $this->getRoute('dashboard'),
            ]
        ]);
    }

    /**
     * Send new CSRF token in HTTP headers
     */
    private function csrfHeader(): void
    {
        $newCsrf = $this->engine()->form()->csrfCreate();
        $newCsrf = sprintf(
            'csrf-token: %s; %s',
            $newCsrf['csrfKey'],
            $newCsrf['csrfToken']
        );

        header($newCsrf, true);
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

        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->csrfHeader();
            $this->redirectError(404);
        }

        if (!$form->csrfVerify()) {
            $this->csrfHeader();
            $this->callError(400);
        }

        $username = filter_var($form->username, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE) ?? null;
        if (is_null($username)) {
            $this->csrfHeader();
            $errorMessage = _("Nom d'utilisateur incorrect.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Réinitialisation de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $wantedUser = (new User($this->engine()))
            ->where('username', '=', $username, true)
            ->take(1)
            ->get();

        if (is_null($wantedUser) || $wantedUser->username !== $username) {
            $this->csrfHeader();
            $errorMessage = _("Nom d'utilisateur incorrect.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Réinitialisation de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        if (is_null($wantedUser->setResetLink())) {
            $this->csrfHeader();
            $errorMessage = _("Impossible de créer le lien de réinitialisation du mot de passe.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Réinitialisation de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $link = $this->getRoute('passwordreset', [
            'userId' => $wantedUser->id,
            'token' => $wantedUser->resetLinkToken,
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
            $this->csrfHeader();
            $errorMessage = _("Impossible d'envoyer l'email contenant le lien de réinitialisation.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Réinitialisation de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        header("Authorization: Bearer " . $wantedUser->token);

        $headerText = _("Et hop, direction votre boite mail...");
        $messageText = _($wantedUser->displayName . ", le lien de réinitialisation vient d'être déposé dans votre boite mail. Ce lien a une durée de validité, ne trainez pas!");

        $this->render([
            'api' => [
                'wantedUserId' => $wantedUser->id,
                'wantedUserToken' => $wantedUser->token,
                'message' => $messageText,
                //'redirect' => $this->getRoute('home'),
            ],
            'message' => [
                'title' => _("Réinitialisation de votre mot de passe"),
                'header' => $headerText,
                'message' => $messageText,
                //'redirect' => $this->getRoute('home'),
            ]
        ]);
    }

    /**
     * Controller: passwordreset
     * Reset the password
     *
     * @route passwordreset web:GET "/passwordreset/{userId:integer}/{token:string}"
     *
     * @protect flood 2s
     *
     * @return void
     */
    public function passwordreset(int $userId, string $token)
    {
        $user = $this->engine()->user();
        $form = $this->engine()->form();

        if ($user->isLogged()) {
            $this->callError(400);
        }

        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->redirectError(404);
        }

        $wantedUser = (new User($this->engine()))
            ->where('id', '=', $userId, true)
            ->andWhere('active', '=', true)
            ->andWhere('resetLinkToken', '=', $token)
            ->take(1)
            ->get();
        if (is_null($wantedUser)) {
            $errorMessage = _("Ce lien de réinitialisation est incorrect.");
            $this->render([
                'auth/passwordreset' => ['error' => $errorMessage]
            ]);
            return;
        }

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));
        $validityLimit = $wantedUser->resetLinkValidity->setTimezone(new DateTimeZone('UTC'));
        if ($now > $validityLimit) {
            $wantedUser->clearResetLink();

            $errorMessage = _("Ce lien de réinitialisation n'est plus valide.");
            $this->render([
                'auth/passwordreset' => ['error' => $errorMessage]
            ]);
            return;
        }

        $formPasswordResetId = 'passwordreset';

        $this->render([
            'auth/passwordreset' => [
                'wantedUser' => $wantedUser,
                'formIdPasswordReset' => $formPasswordResetId,
                'formMethodPasswordReset' => 'POST',
                'csrfFieldPasswordReset' => $form->csrfHiddenInput($formPasswordResetId),
                'actionPasswordReset' => $this->getRoute('passwordupdate', [
                    'userId' => $wantedUser->id,
                    'token' => $token,
                ]),
            ]
        ]);
    }

    /**
     * Controller: passwordupdate
     * Update the password
     *
     * @route passwordupdate web,api:POST "/passwordupdate/{userId:integer}/{token:string}"
     *
     * @protect flood 2s
     *
     * @return void
     */
    public function passwordupdate(int $userId, string $token)
    {
        $user = $this->engine()->user();
        $form = $this->engine()->form();

        if ($user->isLogged()) {
            $this->callError(400);
        }

        if (Env::get('ALLOW_PUBLIC_LOGIN', 'false') != 'true') {
            $this->csrfHeader();
            $this->redirectError(404);
        }

        if (!$form->csrfVerify()) {
            $this->csrfHeader();
            $this->callError(400);
        }

        $wantedUser = (new User($this->engine()))
            ->where('id', '=', $userId, true)
            ->andWhere('active', '=', true)
            ->andWhere('resetLinkToken', '=', $token)
            ->take(1)
            ->get();
        if (is_null($wantedUser)) {
            $this->csrfHeader();
            $wantedUser->clearResetLink();

            $errorMessage = _("Procédure de réinitialisation corrompue.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Modification de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $now = (new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));
        $validityLimit = $wantedUser->resetLinkValidity->setTimezone(new DateTimeZone('UTC'));
        if ($now > $validityLimit) {
            $this->csrfHeader();
            $wantedUser->clearResetLink();

            $errorMessage = _("Procédure de réinitialisation trop ancienne.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Modification de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $password = filter_var($form->password, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE) ?? null;
        $passwordbis = filter_var($form->passwordbis, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE) ?? null;
        if (is_null($password) || is_null($passwordbis) || $password !== $passwordbis) {
            $this->csrfHeader();
            $errorMessage = _("Mot de passe incorrect. La confirmation est différente de l'original...");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Modification de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        if (!passwordGoodStrength($password)) {
            $this->csrfHeader();
            $errorMessage = _("Mot de passe de mauvaise qualité. Vous devez utiliser au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial et 8 caractères.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Modification de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $wantedUser->password = password($password);
        $updated = $wantedUser->update();
        if (!$updated) {
            $this->csrfHeader();
            $errorMessage = _("Impossible de modifier le mot de passe.");
            $this->render([
                'api' => ['error' => $errorMessage],
                'message' => [
                    'title' => _("Modification de votre mot de passe"),
                    'error' => $errorMessage
                ]
            ]);
            return;
        }

        $wantedUser->clearResetLink();

        header("Authorization: Bearer " . $wantedUser->token);

        $headerText = _("Opération réussie.");
        $messageText = _($wantedUser->displayName . ", votre mot de passe viend d'être modifié. Vous pouvez désormais vous connecter.");

        $this->render([
            'api' => [
                'wantedUserId' => $wantedUser->id,
                'wantedUserToken' => $wantedUser->token,
                'message' => $messageText,
                'redirect' => $this->getRoute('login'),
            ],
            'message' => [
                'title' => _("Modification de votre mot de passe"),
                'header' => $headerText,
                'message' => $messageText,
                'redirect' => $this->getRoute('login'),
            ]
        ]);
    }

}