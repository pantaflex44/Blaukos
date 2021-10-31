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
use Core\Models\User;
use DateTimeImmutable;
use Exception;

/**
 * Auto import class
 *
 * @return void
 */
function autoImport()
{
    spl_autoload_register(
        function (string $class) {
            $items = explode('\\', $class);
            array_walk($items, function (&$value) {
                $value = lcfirst($value);
            });

            $filename = array_pop($items);

            $path = '/' . implode('/', $items);
            if (strlen($path) > 0 && substr($path, -1, 1) != '/') {
                $path .= '/';
            }

            $filepath = '..' . $path . '{**/,}' . $filename . '.inc.php';
            $files = glob($filepath, GLOB_BRACE);

            if (count($files) == 1) {
                require_once $files[0];
            }
        }
    );
}

/**
 * String starts with
 *
 * @param string $haystack The string
 * @param string $needle The search
 * @return bool true, if found, else, false
 */
function startsWith(string $haystack, string $needle): bool
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

/**
 * String ends with
 *
 * @param string $haystack The string
 * @param string $needle The search
 * @return boolean true, if found, else, false
 */
function endsWith(string $haystack, string $needle): bool
{
    $length = strlen($needle);
    if (!$length) {
        return true;
    }

    return substr($haystack, -$length) === $needle;
}

/**
 * Abort treatment
 *
 * @param integer $code HTTP error code
 * @return void
 */
function abort(int $code)
{
    $appType = Env::get('APP_TYPE', 'web');

    if ($appType == 'api') {
        sendJSON(['http' => $code]);
    }

    if ($appType == 'web') {
        http_response_code($code);
        exit;
    }
}

/**
 * Create slug format of text
 *
 * @param string $text Text to convert
 * @param string $divider Slug divider, default '-'
 * @return string Slug created or empty if very bad text
 */
function makeSlug(string $text, string $divider = '-'): string
{
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, $divider);
    $text = preg_replace('~-+~', $divider, $text);
    $text = strtolower($text);

    return $text;
}

/**
 * Convert flat uri to an array and sanitize her
 *
 * @param string $uri Flat uri to convert
 * @return array Uri converted in array
 */
function uriToArray(string $uri): array
{
    $ret = [];

    $ret = explode('/', rawurldecode($uri));

    array_walk(
        $ret,
        function (&$value, string $key) {
            if (preg_match('/\{([0-9a-zA-Z_-]+):([a-z]+)\}/', $value, $result) !== false) {
                if (count($result) > 0) {
                    $value = [
                        'varname' => $result[1],
                        'var' => str_replace($result[0], '$' . $result[1], $value),
                        'type' => $result[2],
                    ];

                    return;
                }
            }

            $value = makeSlug($value);
        }
    );

    $ret = array_values(array_filter(
        $ret,
        fn ($value, string $key): bool => trim($key) != '' && ((is_string($value) && trim($value) != '') || is_array($value)),
        ARRAY_FILTER_USE_BOTH
    ));

    return $ret;
}

/**
 * Reflat uri from an exploded array uri
 *
 * @param array $uri Exploded uri
 * @param array $params Uri params to fill
 * @return string Flatted Uri
 */
function arrayToUri(array $uri, array $params = []): string
{
    $finalUri = $uri;
    for ($i = 0; $i < count($uri); $i++) {
        $item = $uri[$i];

        if (!is_array($item)) {
            $finalUri[$i] = $item;
            continue;
        }

        if (!array_key_exists($item['varname'], $params)) {
            $finalUri[$i] = '{' . $item['varname'] . ':' . $item['type'] . '}';
            continue;
        }

        $finalUri[$i] = strval($params[$item['varname']]);
    }

    return implode('/', $finalUri);
}

/**
 * Return the base URL of the current page
 *
 * @return string Base URL found
 */
function baseUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    return $protocol . $host;
}

/**
 * Send a JSON response
 *
 * @param array $response Response array formated to send
 * @return void
 */
function sendJSON(array $response, ?string $token = null): void
{
    if (!array_key_exists('http', $response)) {
        $response['http'] = 200;
    }

    if (!is_null($token)) {
        $response['token'] = $token;
    }

    header('Content-type: application/json');
    echo json_encode($response);

    exit;
}

/**
 * sha256 hash function
 *
 * @param string $text Texte to hash
 * @return string Hashed text
 */
function sha256(string $text): string
{
    return hash('sha256', $text);
}

/**
 * Compara original text to hash, to a hashed text
 *
 * @param string $original Original non hashed text
 * @param string $hash A hashed text
 * @return boolean true, it's same, else, false
 */
function sha256_compare(string $original, string $hash): bool
{
    return ($hash === sha256($original));
}

/**
 * Hash a password
 *
 * @param string $original The password to hash
 * @return string Hashed password
 */
function password(string $original): string
{
    return password_hash($original, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 *
 * @param string $original The original non hashed password
 * @param string $hash A hashed password
 * @return boolean true, it's same, else, false
 */
function password_compare(string $original, string $hash): bool
{
    return password_verify($original, $hash);
}

/**
 * Log an error
 *
 * @param string $message Message to log
 * @return void
 */
function logError(string $message, string $filename = __FILE__, int $line = __LINE__): void
{
    if (Env::get('APP_DEBUG', 'true') == 'true') {
        $errorMessage = sprintf(
            '[%s] %s {file: %s at line %d}',
            Env::get('APP_NAME'),
            $message,
            $filename,
            $line
        );
        error_log($errorMessage, 0);
    }
}

/**
 * Init the session manager
 *
 * @return void
 */
function initSession(): void
{
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_trans_id', 0);
    ini_set('session.cache_limiter', 'nocache');
    ini_set('session.url_rewriter_tags', 0);
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.cookie_path', '/');
}

/**
 * Start the session manager
 *
 * @return void
 */
function startSession(): void
{
    session_name(Env::get('APP_NAME', 'PHPSESSID'));
    session_start();

    $valid = true;

    if (isset($_SESSION['REMOTE_ADDR'])) {
        $valid &= ($_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR']);
    } else {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }

    if (isset($_SESSION['HTTP_USER_AGENT'])) {
        $valid &= ($_SESSION['HTTP_USER_AGENT'] == $_SERVER['HTTP_USER_AGENT']);
    } else {
        $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
    }

    if (!$valid) {
        clearSession();
        startSession();
        exit;
    }
}

/**
 * Clear the current session
 *
 * @return void
 */
function clearSession(): void
{
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

    session_unset();
    session_destroy();
}

/**
 * Get current host
 *
 * @return string The current host
 */
function getHost(): string
{
    $possibleHostSources = array('HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR');
    $sourceTransformations = array(
        'HTTP_X_FORWARDED_HOST' => function ($value) {
            $elements = explode(',', $value);
            return trim(end($elements));
        }
    );
    $host = '';
    foreach ($possibleHostSources as $source) {
        if (!empty($host)) break;
        if (empty($_SERVER[$source])) continue;
        $host = $_SERVER[$source];
        if (array_key_exists($source, $sourceTransformations)) {
            $host = $sourceTransformations[$source]($host);
        }
    }

    // Remove port number from host
    $host = preg_replace('/:\d+$/', '', $host);

    return trim($host);
}

const JWT_ALGOS = ['hash' => 'sha256'];

/**
 * Create a JWT token
 *
 * @param integer $id User ID
 * @return string The JWT token created
 */
function jwtToken(int $userId): string
{
    $secretKey = Env::get('APP_TOKEN', null);
    if (is_null($secretKey)) {
        logError('App token not found', __FILE__, __LINE__);

        header('location: /500');
        exit;
    }

    $issuedAt = new DateTimeImmutable();
    $expire = $issuedAt
        ->modify('+' . Env::get('APP_TOKEN_DELAY', '10 minutes'))
        ->getTimestamp();
    $serverName = getHost();

    $headers = rawurlencode(base64_encode(json_encode([
        'alg'       => 'hash',
        'typ'       => 'JWT'
    ])));

    $payload = rawurlencode(base64_encode(json_encode([
        'iat'       => $issuedAt->getTimestamp(),
        'iss'       => $serverName,
        'nbf'       => $issuedAt->getTimestamp(),
        'exp'       => $expire,
        'uid'       => $userId,
    ])));

    $signature = rawurlencode(base64_encode(hash_hmac(
        JWT_ALGOS['hash'],
        "$headers.$payload",
        $secretKey,
        true
    )));

    $token = "$headers.$payload.$signature";

    return $token;
}

/**
 * Verify the JWT token
 *
 * @return boolean
 */
function isAuth(Engine $engine): ?User
{
    $secretKey = Env::get('APP_TOKEN', null);
    if (is_null($secretKey)) {
        logError('App token not found', __FILE__, __LINE__);

        header('location: /500');
        exit;
    }

    $unsetAuth = function () {
        if (isset($_SESSION['JWT_TOKEN'])) {
            unset($_SESSION['JWT_TOKEN']);
        }
    };

    $token = '';

    if (Env::get('APP_TYPE') == 'api') {
        $authorization = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? $_SERVER['HTTP_AUTHORIZATION']
            : null;

        if (is_null($authorization)) {
            $unsetAuth();
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = $matches[1];
            if (!$token) {
                $unsetAuth();
                return null;
            }
        }
    }

    if (Env::get('APP_TYPE') == 'web') {
        if (!isset($_SESSION['JWT_TOKEN'])) {
            $unsetAuth();
            return null;
        }

        $token = $_SESSION['JWT_TOKEN'];
    }

    try {
        $explodes = explode('.', $token);
        if (count($explodes) != 3) {
            $unsetAuth();
            return null;
        }
        list($headers64, $payload64, $signature64) = $explodes;

        $headers = json_decode(base64_decode(rawurldecode($headers64)), true);
        $payload = json_decode(base64_decode(rawurldecode($payload64)), true);
        $signature = base64_decode(rawurldecode($signature64));

        if ($headers['typ'] != 'JWT' || !in_array($headers['alg'], array_keys(JWT_ALGOS))) {
            $unsetAuth();
            return null;
        }

        $hash = hash_hmac(
            JWT_ALGOS[$headers['alg']],
            "$headers64.$payload64",
            $secretKey,
            true
        );

        if (!hash_equals($signature, $hash)) {
            $unsetAuth();
            return null;
        }

        if ($payload['iss'] != getHost()) {
            $unsetAuth();
            return null;
        }

        $now = (new DateTimeImmutable())->getTimestamp();

        if (
            $payload['iat'] >= $now
            || $payload['nbf'] >= $now
            || $payload['exp'] < $now
        ) {
            $unsetAuth();
            return null;
        }

        $user = new User($engine, $payload['uid']);
        if (is_null($user)) {
            $unsetAuth();
            return null;
        }
        if ($user->token != $token) {
            $unsetAuth();
            return null;
        }

        $payload['nbf'] = (new DateTimeImmutable())->getTimestamp();
        $payload['exp'] = (new DateTimeImmutable())
            ->modify('+' . Env::get('APP_TOKEN_DELAY', '10 minutes'))
            ->getTimestamp();
        $payload64 = rawurlencode(base64_encode(json_encode($payload)));

        $signature64 = rawurlencode(base64_encode(hash_hmac(
            JWT_ALGOS['hash'],
            "$headers64.$payload64",
            $secretKey,
            true
        )));

        $token = "$headers64.$payload64.$signature64";
        $token = $user->updateToken($token);
        if (is_null($token)) {
            $unsetAuth();
            return null;
        }

        // share the cookie for future use
        header("Authorization: Bearer $token");     # http authorization header for api
        $_SESSION['JWT_TOKEN'] = $token;            # session cookie for web app

        return $user;
    } catch (Exception $ex) {
        $unsetAuth();
        return null;
    }
}
