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

namespace Core\Libs\Twig;

use Core\Engine;
use Core\Libs\Env;
use Core\Libs\Settings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CustomTwigExtensions extends AbstractExtension
{

    private Engine $_engine;

    public function __construct(Engine $engine)
    {
        $this->_engine = $engine;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('_', '_'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('env', function (string $key, string $default = ''): string {
                $value = Env::get($key, $default);

                switch ($value) {
                    case 'true':
                        $value = true;
                        break;
                    case 'false':
                        $value = false;
                        break;
                }

                return $value;
            }),

            new TwigFunction('settings', function (string $key, $default) {
                return Settings::get($key, $default);
            }),

            new TwigFunction('route', function (string $name, array $params = []): string {
                return $this->_engine->route()->get($name, $params);
            }),

            /*new \Twig\TwigFunction('js_path', function () {
                return dir2url(PARAMS['pathes']['web'] . getTheCaller() . '/js/');
            }),

            new \Twig\TwigFunction('css_path', function () {
                return dir2url(PARAMS['pathes']['web'] . getTheCaller() . '/styles/css/');
            }),

            new \Twig\TwigFunction('images_path', function () {
                return dir2url(PARAMS['pathes']['web'] . getTheCaller() . '/images/');
            }),

            new \Twig\TwigFunction('local_path', function (string $dirname) {
                return dir2url($dirname);
            }),

            new \Twig\TwigFunction('self_path', function () {
                return $_SERVER['PHP_SELF'];
            }),

            new \Twig\TwigFunction('csrf_field', function (string $formId) {
                $csrf = newCsrfToken($formId);
                return '<input type="hidden" id="' . $formId . '-csrf" name="' . $csrf->name . '" value="' . $csrf->token . '">';
            }),

            new \Twig\TwigFunction('datetime', function (string $datetime, string $from, string $to) {
                $tsp = strptime($datetime, $from);
                $ts = mktime(
                    $tsp['tm_hour'],
                    $tsp['tm_min'],
                    $tsp['tm_sec'],
                    $tsp['tm_mon'] + 1,
                    $tsp['tm_mday'],
                    $tsp['tm_year'] + 1900
                );
                return strftime($to, $ts);
            }),

            new \Twig\TwigFunction('humanDateDiffFromNow', function (string $datetime) {
                $ts = time() - strtotime($datetime);

                $years = floor($ts / 31536000);
                $days = floor(($ts - ($years * 31536000)) / 86400);
                $hours = floor(($ts - ($years * 31536000 + $days * 86400)) / 3600);
                $minutes = floor(($ts - ($years * 31536000 + $days * 86400 + $hours * 3600)) / 60);

                $timestring = '';
                if ($years > 0) {
                    $timestring .= $years . 'a ';
                }
                if ($days > 0) {
                    $timestring .= $days . 'd ';
                }
                if ($hours > 0) {
                    $timestring .= $hours . 'hr';
                }
                if ($minutes > 0) {
                    $timestring .= $minutes . 'mins';
                }

                return $timestring;
            }),

            new \Twig\TwigFunction('localeInfos', function (string $locale, string $lang = 'fr') {
                return (object)[
                    'displayName' => ucfirst(locale_get_display_name($locale, $lang)),
                    'lang' => locale_get_primary_language($locale),
                    'region' => strtolower(locale_get_region($locale)),
                ];
            }),*/
        ];
    }
}
