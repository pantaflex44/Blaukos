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

namespace Core;

use Core\Libs\Annotations;
use Core\Libs\Env;
use Core\Libs\Database;
use Core\Libs\Form;
use Core\Libs\IDB;
use Core\Libs\Protect;
use Core\Libs\Route;
use Core\Libs\Settings;
use Core\Libs\Template;
use Core\Libs\Translation;
use Core\Models\User;
use Exception;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

use function Core\Libs\auth;
use function Core\Libs\autoImport;
use function Core\Libs\initSession;
use function Core\Libs\logError;
use function Core\Libs\startSession;

/**
 * Import utilities
 */
require_once __DIR__ . '/libs/utilities.inc.php';

/**
 * Composer autoloader
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Twig custom extensions
 */
require_once __DIR__ . '/libs/twig/extensions.inc.php';

/**
 * Autoload all custom objects files by their class names
 */
autoImport();

/**
 * Main public entry point
 */
class Engine
{

    private ?IDB $_db = null;
    private Route $_route;
    private Form $_form;
    private Template $_template;
    private Translation $_translation;
    private ?User $_user;
    private Protect $_protect;

    /**
     * Return the database manager
     *
     * @return IDB|null The database manager or null 
     */
    public function db(): ?IDB
    {
        return $this->_db;
    }

    /**
     * Routes manager
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->_route;
    }

    /**
     * Form manager
     *
     * @return Form
     */
    public function form(): Form
    {
        return $this->_form;
    }

    /**
     * Form manager
     *
     * @return Form
     */
    public function template(): Template
    {
        return $this->_template;
    }

    /**
     * Translation manager
     *
     * @return Translation
     */
    public function tr(): Translation
    {
        return $this->_translation;
    }

    /**
     * Current user
     *
     * @return User
     */
    public function user(): User
    {
        return $this->_user;
    }

    /**
     * App protection
     *
     * @return Protect
     */
    public function protect(): Protect
    {
        return $this->_protect;
    }

    /**
     * The constructor
     */
    public function __construct()
    {
        // load .env files
        Env::load();

        // load settings
        Settings::load();

        // init and start session manager
        initSession();
        startSession();

        // load the database manager
        $this->_db = Database::instance();

        // load translation manager
        $this->_translation = new Translation($this);

        // load application protection
        $this->_protect = new Protect($this);

        // give access to Form manager
        $this->_form = new Form($this);

        // give access to Route manager and load default routes
        $this->_route = new Route($this);

        // scan all custum's annotations
        Annotations::scan($this);

        // load the template manager
        $this->_template = new Template($this);

        // load the current user
        $this->_user = auth($this);
        if (is_null($this->_user)) {
            $this->_user = new User($this);
        }
        if ($this->_user->isLogged()) {
            $this->_translation->setCurrent($this->_user->locale, true);
        }
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        Settings::save();
    }

    /**
     * Send a mail
     *
     * @param array|string $to To email address or array of [ 'email address', 'name' ]
     * @return boolean true, mail sended, else, false
     */
    public function sendMail($to, string $subject, string $templateName, array $templateParams = []): bool
    {
        $isDebug = filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
        $mailer = new PHPMailer($isDebug);

        try {
            $mailer->isSMTP();
            $mailer->SMTPDebug = 0;
            $mailer->Host = Env::get('SMTP_HOST', '');
            $mailer->Port = Env::get('SMTP_PORT', '');
            if (trim(Env::get('SMTP_USERNAME', '')) != '') {
                $mailer->SMTPAuth = true;
                $mailer->SMTPSecure = Env::get('SMTP_SECURE', '');
                $mailer->Username = Env::get('SMTP_USERNAME', '');
                $mailer->Password = Env::get('SMTP_PASSWORD', '');
            } else {
                $mailer->SMTPAuth = false;
            }
            $mailer->addCustomHeader('X-SES-CONFIGURATION-SET', 'ConfigSet');

            $mailer->setFrom(
                Env::get('SMTP_FROM_EMAIL', ''),
                Env::get('SMTP_FROM_NAME', '')
            );

            $toName = '';
            $toEmail = '';
            if (is_array($to) && count($to) > 0) {
                $toEmail = $to[0];
                if (count($to) > 1) {
                    $toName = $to[1];
                }
            } else {
                $toEmail = strval($to);
            }
            $mailer->addAddress($toEmail, $toName);

            $mailer->CharSet = 'UTF-8';
            $mailer->isHtml();
            $mailer->Subject = sprintf(
                '[%s] %s',
                Env::get('APP_NAME'),
                trim($subject)
            );
            $mailer->Body = $this->template()->prepare(
                sprintf('mails/html_%s', $templateName),
                $templateParams
            );
            $mailer->AltBody = $this->template()->prepare(
                sprintf('mails/text_%s', $templateName),
                $templateParams
            );

            if (!$mailer->send()) {
                return false;
            }

            return true;
        } catch (PHPMailerException $pmex) {
            logError(
                sprintf(
                    'PHPMailer error: (%s) %s',
                    $pmex->getCode(),
                    $pmex->getMessage()
                ),
                $pmex->getFile(),
                $pmex->getLine()
            );
            return false;
        } catch (Exception $ex) {
            logError(
                sprintf(
                    'Send mail function error: (%s) %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                $ex->getFile(),
                $ex->getLine()
            );
            return false;
        }
    }
}
