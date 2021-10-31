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
