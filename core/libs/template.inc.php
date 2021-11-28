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

namespace Core\Libs;

use Core\Engine;
use Core\Libs\Twig\CustomTwigExtensions;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Manage templates
 */
class Template
{
    private Engine $_engine;
    private ?Environment $_twig = null;

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;

        // load the template manager if is a web app
        if (Env::get('APP_TYPE') == 'web') {
            $twig = new FilesystemLoader(__DIR__ . '/../views');
            $this->_twig = new Environment($twig, [
                'cache' => filter_var(Env::get('APP_USECACHE', 'true'), FILTER_VALIDATE_BOOLEAN)
                    ? __DIR__ . '/../views/cache'
                    : false,
                'debug' => filter_var(Env::get('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
                'charset' => 'utf-8',
            ]);
            $this->_twig->addExtension(new CustomTwigExtensions($this->_engine));
        }
    }

    /**
     * Render web page from template name with params
     *
     * @param string $name Template name
     * @param array $params Template params
     * @return void
     */
    public function render(string $name, array $params = [])
    {
        $content = $this->prepare($name, $params);
        if (is_null($content)) {
            return;
        }

        echo $content;
    }

    /**
     * Prepare web page from template name with params
     *
     * @param string $name Template name
     * @param array $params Template params
     * @return null|string Prepared template
     */
    public function prepare(string $name, array $params = []): ?string
    {
        // if not a web app, return
        if (Env::get('APP_TYPE') != 'web') {
            return null;
        }

        $names = explode('/', $name);
        $templateName = array_pop($names);

        $enums = isset($GLOBALS['enums']) ? $GLOBALS['enums'] : null;

        $htmldir = $this->_engine->user()->isLogged()
            ? $this->_engine->user()->htmldir
            : Settings::get('html_dir', 'ltr');

        // else, load and show template page
        $content = $this->twig()->render(
            $name . '.html.twig',
            array_merge(
                [
                    'enums' => $enums,
                    'user' => $this->_engine->user(),
                    'templateName' => $templateName,
                    'locale' => $this->_engine->tr()->getCurrent(),
                    'lang' => $this->_engine->tr()->getLanguageCode(),
                    'dir' => $htmldir,
                    'charset' => 'UTF-8',
                ],
                $params
            )
        );

        return $this->_sanitizeOutput($content);
    }

    /**
     * Template manager
     *
     * @return mixed
     */
    public function twig(): ?Environment
    {
        return $this->_twig;
    }

    /**
     * From Zend Framework - Sanitize HTML output
     * @param string $html HTML string
     * @return string Sanitized HTML string
     */
    private function _sanitizeOutput(string $html): string
    {
        $html = trim($html);
        //remove redundant (white-space) characters
        $replace = array(
            //remove tabs before and after HTML tags
            '/\>[^\S ]+/s' => '>',
            '/[^\S ]+\</s' => '<',
            //shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
            '/([\t ])+/s' => ' ',
            //remove leading and trailing spaces
            '/^([\t ])+/m' => '',
            '/([\t ])+$/m' => '',
            // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
            '~//[a-zA-Z0-9 ]+$~m' => '',
            //remove empty lines (sequence of line-end and white-space characters)
            '/[\r\n]+([\t ]?[\r\n]+)+/s' => "\n",
            //remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
            '/\>[\r\n\t ]+\</s' => '><',
            //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
            '/}[\r\n\t ]+/s' => '}',
            '/}[\r\n\t ]+,[\r\n\t ]+/s' => '},',
            //remove new-line after JS's function or condition start; join with next line
            '/\)[\r\n\t ]?{[\r\n\t ]+/s' => '){',
            '/,[\r\n\t ]?{[\r\n\t ]+/s' => ',{',
            //remove new-line after JS's line end (only most obvious and safe cases)
            '/\),[\r\n\t ]+/s' => '),',
            //remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
            '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
        );
        $html = preg_replace(array_keys($replace), array_values($replace), $html);

        //remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
        $remove = array(
            '</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
        );
        return str_ireplace($remove, '', $html);
    }
}