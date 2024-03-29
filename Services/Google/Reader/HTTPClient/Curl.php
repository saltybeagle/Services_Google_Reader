<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains a cURL-based HTTP client class for the Services_Google_Reader package
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
 * @package   Services_Google_Reader
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Google_Reader
 */

/**
 * PEAR Exception.
 */
require_once 'PEAR/Exception.php';

/**
 * Exception thrown when a communications error occurs.
 */
require_once 'Services/Google/Reader/CommunicationException.php';

/**
 * HTTP client interface.
 */
require_once 'Services/Google/Reader/HttpClient.php';

// {{{ class Services_Google_Reader_HttpClient_Curl

/**
 * cURL-based simple HTTP client for accessing the Akismet REST API
 *
 * This cURL-based HTTP client requires the cURL extension for PHP.
 *
 * This HTTP client only supports the HTTP POST method since that is all that
 * is needed for the Akismet API.
 *
 * @category  Services
 * @package   Services_Google_Reader
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Akismet
 * @link      http://akismet.com/development/api/
 */
class Services_Google_Reader_HttpClient_Curl extends Services_Google_Reader_HttpClient
{
    // {{{ private properties

    /**
     * Akismet API server host name
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Curl::__construct()
     */
    private $_host = '';

    /**
     * TCP/IP Port on which to connect
     *
     * @var integer
     *
     * @see Services_Google_Reader_HttpClient_Curl::__construct()
     */
    private $_port = 80;

    /**
     * HTTP user agent string of this HTTP client
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Curl::__construct()
     */
    private $_user_agent = '';

    /**
     * Whether or not this client is connected
     *
     * @var boolean
     *
     * @see Services_Google_Reader_HttpClient_Curl::_connect()
     * @see Services_Google_Reader_HttpClient_Curl::_disconnect()
     */
    private $_connected = false;

    /**
     * cURL handle of this HTTP client
     *
     * @var resource
     */
    private $_curl_handle = null;

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
     *         getting the cURL response from Akismet API server.
     */
    public function post($path, $content, $api_key = '')
    {
        $this->_connect();

        if (strlen($api_key) > 0) {
            $host = $api_key . '.' . $this->_host;
        } else {
            $host = $this->_host;
        }

        $url = sprintf('http://%s%s', $host, $path);

        curl_setopt_array($this->_curl_handle, array(
            CURLOPT_URL        => $url,
            CURLOPT_POSTFIELDS => $content
        ));

        $response = curl_exec($this->_curl_handle);
        if ($response === false) {
            $error      = curl_error($this->_curl_handle);
            $error_code = curl_errno($this->_curl_handle);
            throw new Services_Google_Reader_CommunicationException('Error getting ' .
                'response from API server: ' . $error, $error_code);
        }

        $this->_disconnect();

        return $response;
    }

    // }}}
    // {{{ __destruct()

    /**
     * Disconnects this HTTP client if it is connected when destroyed
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_disconnect();
    }

    // }}}
    // {{{ __construct()

    /**
     * Creates a new cURL-based HTTP client for accessing the Akismet REST API
     *
     * Instances of this HTTP client must be instantiated using the
     * {@link Services_Google_Reader_HttpClient::factory()} method.
     *
     * @param string  $host       the Akismet API server host name.
     * @param integer $port       the TCP/IP connection port of this HTTP
     *                            client.
     * @param string  $user_agent the HTTP user agent of this HTTP client.
     *
     * @throws PEAR_Exception if the cURL extension is not loaded for this PHP
     *         installation.
     */
    protected function __construct($host, $port, $user_agent)
    {
        $this->_host       = strval($host);
        $this->_port       = intval($port);
        $this->_user_agent = strval($user_agent);

        if (!extension_loaded('curl')) {
            throw new PEAR_Exception('The cURL library is not enabled for ' .
                'this PHP installation. The cURL-based HTTP client may not ' .
                'be used.');
        }
    }

    // }}}
    // {{{ _connect()

    /**
     * Connects this HTTP client to the Akismet API server
     *
     * The connection is only performed if this client is disconnected.
     *
     * @return void
     */
    private function _connect()
    {
        if (!$this->_connected) {
            $this->_curl_handle = curl_init();
            curl_setopt_array($this->_curl_handle, array(
                CURLOPT_POST           => true,
                CURLOPT_PORT           => $this->_port,
                CURLOPT_USERAGENT      => $this->_user_agent,
                CURLOPT_RETURNTRANSFER => true
            ));

            $this->_connected = true;
        }
    }

    // }}}
    // {{{ _disconnect()

    /**
     * Disconnects this HTTP client from the Akismet API server
     *
     * If there is remaining data in incomming packets, the data is read and
     * discarded before the connection is closed. Disconnection is only
     * performed if this client is connected.
     *
     * @return void
     */
    private function _disconnect()
    {
        if ($this->_connected) {
            curl_close($this->_curl_handle);
            $this->_curl_handle = null;
            $this->_connected   = false;
        }
    }

    // }}}
}

// }}}
