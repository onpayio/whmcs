<?php

/*
 * Copyright (c) 2017, 2018 François Kooman <fkooman@tuxed.net>
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

$baseDir = \dirname(__DIR__);
require_once \dirname(__DIR__).'/vendor/autoload.php';

use fkooman\OAuth\Client\ErrorLogger;
use fkooman\OAuth\Client\Exception\AuthorizeException;
use fkooman\OAuth\Client\Exception\TokenException;
use fkooman\OAuth\Client\Http\CurlHttpClient;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use fkooman\OAuth\Client\SessionTokenStorage;

// absolute link to index.php in this directory
// after handling the callback, we redirect back to this URL...
$indexUri = 'http://localhost:8081/index.php';

// the user ID to bind to, typically the currently logged in user on the
// _CLIENT_ service...
$userId = 'foo';

try {
    // we assume your application has proper (SECURE!) session handling
    if (PHP_SESSION_ACTIVE !== \session_status()) {
        \session_start();
    }

    $client = new OAuthClient(
        // for DEMO purposes we store the AccessToken in the user session
        // data...
        new SessionTokenStorage(),
        // for DEMO purposes we also allow connecting to HTTP URLs, do **NOT**
        // do this in production
        new CurlHttpClient(['allowHttp' => true], new ErrorLogger())
    );

    // handle the callback from the OAuth server
    $client->handleCallback(
        new Provider(
            'demo_client',                          // client_id
            'demo_secret',                          // client_secret
            'http://localhost:8080/authorize.php',  // authorization_uri
            'http://localhost:8080/token.php'       // token_uri
        ),
        $userId, // the userId to bind the access token to
        $_GET
    );

    // redirect the browser back to the index
    \http_response_code(302);
    \header(\sprintf('Location: %s', $indexUri));
} catch (AuthorizeException $e) {
    // in case the "Authorization Server" refuses our request, e.g. the user
    // denied the authorization, we may ask the user again in case they want
    // to reconsider giving authorization...
    echo \sprintf('%s: %s', \get_class($e), $e->getMessage());
    if (null !== $errorDescription = $e->getDescription()) {
        echo \sprintf('(%s)', $errorDescription);
    }
} catch (TokenException $e) {
    // there was a problem obtaining an access_token, show response to ease
    // debugging... (this does NOT happen in normal circumstances)
    echo \sprintf('%s: %s', \get_class($e), $e->getMessage());
    echo \var_export($e->getResponse(), true);
} catch (Exception $e) {
    // for all other errors, there is nothing we can do...
    echo \sprintf('%s: %s', \get_class($e), $e->getMessage());
}
