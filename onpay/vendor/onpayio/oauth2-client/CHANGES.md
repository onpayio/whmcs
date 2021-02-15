# Changes

## 7.2.0 (2020-04-25)
- always use PKCE
- use 256 bits random data for "state"
- simplify `RandomInterface` and implementation
- use separate JSON class
- remove some "setters" from `OAuthClient` class as they are only needed for
  testing

## 7.1.3 (2018-06-02)
- no need to use `strcasecmp` 

## 7.1.2 (2018-05-22)
- do NOT log `Authorization: Basic` request header

## 7.1.1 (2018-05-22)
- include a very simple `psr/log` logger `ErrorLogger`
- mention `psr/log` support in the README.md

## 7.1.0 (2018-05-18)
- implement support for PSR logging HTTP request and response when interacting
  with OAuth servers and resource servers
 
## 7.0.0 (2018-04-12)
- update API
  - `OAuthClient` methods `get`, `post`, `send`, `getAuthorizeUri`, 
    `handleCallback` require `Provider` and `userId` parameters now
  - remove `OAuthClient::setUserId` and `OAuthClient::setProvider` as they are 
    now parameters to above mentioned methods
  - reduce signature of `OAuthClient` constructor, introduce `setSession`, 
    `setRandom` and `setDateTime`. **NOTE** that these are only for testing or 
    very special use cases, so most likely not for normal API users!
- remove `OAuthClient::hasAccessToken` as no code actually uses it
- fix some `vimeo/psalm` warnings/errors

## 6.0.2 (2018-03-21)
- use safe `strlen` from `paragonie/constant_time_encoding`
- fix `vimeo/psalm` and `phpstan/phpstan` errors and warnings

## 6.0.1 (2017-11-28)
- include `Accept` header in token requests

## 6.0.0 (2017-11-27)
- simplify error handling when obtaining `access_token` and `refresh_token`
- modify `OAuthClient::handleCallback` to take array with query parameters, 
  i.e. `$_GET` to also handle error responses from the AS
- introduce `AuthorizeException` for when OAuth server refuses to grant 
  the authorization, e.g. the user does not allow it
- make all exceptions extend `OAuthException`
- introduce `TokenException` that contains a message and the `Response` object
  to ease debugging in case the `access_token` is not granted due to server 
  or configuration error
- Have `Random::getHex` and `Random::getRaw` instead of one method with boolean 
  parameter
- remove PKCE, it is only useful on OAuth clients where leaking the 
  authorization code is a risk (when not using client credentials)
- `Provider` MUST have secret now (because of PKCE removal)

## 5.0.3 (2017-11-16)
- also support `Bearer` as `token_type` in addition to `bearer` (issue #12)

## 5.0.2 (2017-11-08)
- add support for PHPUnit 6
- various fixes to solve token removal in case of expiry (issue #10)

## 5.0.1 (2017-09-18)
- many small code style improvements
- more complete phpdoc
- fix issues found by [Psalm](https://getpsalm.org/) and 
  [phpstan](https://github.com/phpstan/phpstan)
 
## 5.0.0 (2017-07-06)
- complete rewrite of the OAuth client, everything is different

## 4.0.1 (2017-01-19)
- update `RandomInterface` a bit to allow specifying the length of the secret
- update (C) year
- fix a HTTP client bug where a potential non array could be returned

## 4.0.0 (2017-01-04)
- remove Guzzle dependency

## 3.0.2 (2016-01-03)
- be less restrictive for `paragonie/random_compat` dependency

## 3.0.1 (2016-12-12)
- change license to AGPLv3+

## 3.0.0 (2016-11-25)
- if token endpoint does not return a scope value, the scope from the request
  is assumed to be granted (according to specification)
- code cleanup
- HTTP clients should now return array instead of JSON string
- restore Guzzle client again
- remove cURL client again, too hard to get right

## 2.0.2 (2016-11-15)
- use PSR-4 now

## 2.0.1 (2016-09-29)
- fix `expires_in` response from token endpoint, add test for it

## 2.0.0 (2016-08-30)
- remove Guzzle client
- add simple cURL client

## 1.0.1 (2016-06-04)
- add API documentation
- improve input validation

## 1.0.0 (2016-05-30)
- initial release
