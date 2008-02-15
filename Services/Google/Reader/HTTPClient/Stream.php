<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains a streams-based HTTP client class for the Services_Akismet package
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008 silverorange
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category  Services
 * @package   Services_Akismet
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Akismet
 */

/**
 * Exception thrown when a communications error occurs.
 */
require_once 'Services/Google/Reader/CommunicationException.php';

/**
 * HTTP client interface.
 */
require_once 'Services/Google/Reader/HttpClient.php';

// {{{ class Services_Google_Reader_HttpClient_Stream

/**
 * Streams-based simple HTTP client for accessing the Akismet REST API
 *
 * This streams-based HTTP client requires PHP to support the
 * stream_context_create(), stream_context_set_option() and file_get_contents()
 * functions and requires that the HTTP stream wrapper is enabled.
 *
 * This HTTP client only supports the HTTP POST method since that is all that
 * is needed for the Akismet API.
 *
 * @category  Services
 * @package   Services_Akismet
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Akismet
 * @link      http://akismet.com/development/api/
 */
class Services_Google_Reader_HttpClient_Stream extends Services_Google_Reader_HttpClient
{
    // {{{ private properties

    /**
     * Akismet API server host name
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Stream::__construct()
     */
    private $_host = '';

    /**
     * TCP/IP Port on which to connect
     *
     * @var integer
     *
     * @see Services_Google_Reader_HttpClient_Stream::__construct()
     */
    private $_port = 80;

    /**
     * HTTP user agent string of this HTTP client
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Stream::__construct()
     */
    private $_user_agent = '';

    /**
     * The stream context used for HTTP connections
     *
     * @var resource
     *
     * @see http://ca.php.net/manual/en/wrappers.http.php
     * @see Services_Google_Reader_HttpClient_Stream::__construct()
     * @see Services_Google_Reader_HttpClient_Stream::post()
     */
    private $_stream_context = null;

    // }}}
    // {{{ post()

    /**
     * Makes a HTTP POST request on the Akismet API server
     *
     * @param string $path    the resource to post to.
     * @param string $content the data to post.
     * @param string $api_key optional. The Wordpress API key to use for the
     *                        request. If not specified, no API key information
     *                        is included in the request. This is used for key
     *                        validation.
     *
     * @return string the content of the HTTP response from the Akismet API
     *                server.
     *
     * @throws Services_Google_Reader_CommunicationException if there is an error
     *         reading from the HTTP stream.
     */
    public function post($path, $content, $api_key = '')
    {
        if (strlen($this->_port) == 0) {
            $url = sprintf('http://%s%s', $this->_host, $path);
        } else {
            $url = sprintf('http://%s:%s%s', $this->_host, $this->_port, $path);
        }

        if (strlen($api_key) > 0) {
            $host_header = $api_key . '.' . $this->_host;
        } else {
            $host_header = $this->_host;
        }

        $headers = array(
            'Host'         => $host_header,
            'Content-type' => 'application/x-www-form-urlencoded; charset=utf-8',
        );

        $header = '';
        foreach ($headers as $key => $value) {
            $header .= $key . ': ' . $value . "\r\n";
        }

        $stream_options = array(
            'http' => array(
                'header'  => $header,
                'content' => $content
            )
        );

        // set header and post data content on stream context
        $result = stream_context_set_option($this->_stream_context,
            $stream_options);

        // read response
        $response = @file_get_contents($url, false, $this->_stream_context);

        if ($response === false) {
            throw new Services_Google_Reader_CommunicationException('Error reading ' .
                'HTTP stream.');
        }

        return $response;
    }

    // }}}
    // {{{ __construct()

    /**
     * Creates a new streams-based HTTP client for accessing the Akismet REST
     * API
     *
     * Instances of this HTTP client must be instantiated using the
     * {@link Services_Google_Reader_HttpClient::factory()} method.
     *
     * @param string  $host       the Akismet API server host name.
     * @param integer $port       the TCP/IP connection port of this HTTP
     *                            client.
     * @param string  $user_agent the HTTP user agent of this HTTP client.
     *
     * @throws PEAR_Exception if the HTTP streams wrapper is not enabled for
     *         this PHP installation.
     */
    protected function __construct($host, $port, $user_agent)
    {
        $this->_host       = strval($host);
        $this->_port       = intval($port);
        $this->_user_agent = strval($user_agent);

        // make sure we have the HTTP wrapper enabled
        $stream_wrappers = stream_get_wrappers();
        if (!in_array('http', $stream_wrappers)) {
            throw new PEAR_Exception('HTTP streams wrapper is not enabled ' .
                'for this PHP installation. The streams-based HTTP client ' .
                'may not be used.');
        }

        // create stream context
        $stream_options        = array('http' => array('method' => 'POST'));
        $this->_stream_context = stream_context_create($stream_options);
    }

    // }}}
}

// }}}
