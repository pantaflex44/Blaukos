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
 * Manage templates
 */
class Translation
{

    private const FILE = __DIR__ . '/../locales/';

    private Engine $_engine;
    private string $_currentLocale;

    /**
     * Constructor
     */
    public function __construct(Engine $engine)
    {
        $this->_engine  = $engine;

        $locale = Settings::get('locale', Env::get('APP_DEFAULT_LOCALE', 'fr_FR'));

        if (!$this->setCurrent($locale)) {
            $this->_currentLocale = Env::get('APP_DEFAULT_LOCALE', 'fr_FR');
        }

        if (stripos(PHP_OS, 'win') === 0) {
            putenv("LC_ALL={$this->_currentLocale}");
        } else {
            setlocale(LC_ALL, $this->_currentLocale . '.utf8');
        }

        $domain = 'messages';
        bindtextdomain($domain, self::FILE);
        textdomain($domain);
    }

    /**
     * Set the current locale
     *
     * @param string|null $locale Wanted locale or null to set default
     * @return boolean true, if locale setted, else, false
     */
    public function setCurrent(?string $locale): bool
    {
        if (!is_null($locale) && in_array($locale, $this->getAvaillables())) {
            $this->_currentLocale = $locale;

            Settings::set('locale', $this->_currentLocale);

            return true;
        }

        if (is_null($locale)) {
            $this->_currentLocale = Env::get('APP_DEFAULT_LOCALE', 'fr_FR');

            Settings::set('locale', $this->_currentLocale);

            return true;
        }

        return false;
    }

    /**
     * Get the current locale
     *
     * @return string The current locale
     */
    public function getCurrent(): string
    {
        return $this->_currentLocale;
    }

    /**
     * Return the default locale
     *
     * @return string The default locale
     */
    public function getDefault(): string
    {
        return  Env::get('APP_DEFAULT_LOCALE', 'fr_FR');
    }

    /**
     * Get the language code (eg: fr)
     *
     * @return string The language code
     */
    public function getLanguageCode(): string
    {
        return locale_get_primary_language($this->_currentLocale);
    }

    /**
     * Get the region code (eg: FR)
     *
     * @return string The region code
     */
    public function getRegionCode(): string
    {
        return locale_get_region($this->_currentLocale);
    }

    /**
     * List availlable locales
     *
     * @return array Availlable locales
     */
    public function getAvaillables(): array
    {
        $locales = array_map(
            fn ($dirname) => is_dir(self::FILE . $dirname) && $dirname !== '.' && $dirname !== '..'
                ? explode('.', $dirname)[0]
                : '',
            scandir(self::FILE)
        );

        $locales = array_values(array_filter($locales, fn ($locale) => !is_null($locale) && $locale !== ''));

        return $locales;
    }
}
