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
                return Env::get($key, $default);
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
