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

namespace fkooman\OAuth\Client\Http;

use fkooman\OAuth\Client\Http\Exception\ResponseException;
use fkooman\OAuth\Client\Json;

class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /** @var array <string,string> */
    private $responseHeaders;

    /**
     * @param int                  $statusCode
     * @param string               $responseBody
     * @param array<string,string> $responseHeaders
     */
    public function __construct($statusCode, $responseBody, array $responseHeaders = [])
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $responseHeaders = [];
        foreach ($this->responseHeaders as $k => $v) {
            $responseHeaders[] = \sprintf('%s: %s', $k, $v);
        }

        return \sprintf(
            '[statusCode=%d, responseHeaders=[%s], responseBody=%s]',
            $this->statusCode,
            \implode(', ', $responseHeaders),
            $this->responseBody
        );
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasHeader($key)
    {
        foreach (\array_keys($this->responseHeaders) as $k) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getHeader($key)
    {
        foreach ($this->responseHeaders as $k => $v) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return $v;
            }
        }

        throw new ResponseException(\sprintf('header "%s" not set', $key));
    }

    /**
     * @return mixed
     */
    public function json()
    {
        if (false === \strpos($this->getHeader('Content-Type'), 'application/json')) {
            throw new ResponseException('response MUST have JSON content type');
        }

        return Json::decode($this->responseBody);
    }

    /**
     * @return bool
     */
    public function isOkay()
    {
        return 200 <= $this->statusCode && 300 > $this->statusCode;
    }
}
