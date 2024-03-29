<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains a socket-based HTTP client class for the Services_Akismet package
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2007-2008 Bret Kuhns, 2008 silverorange
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
 * @author    Bret Kuhns
 * @copyright 2007-2008 Bret Kuhns, 2008 silverorange
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

// {{{ class Services_Google_Reader_HttpClient_Socket

/**
 * Socket-based simple HTTP client for accessing the Akismet REST API
 *
 * This socket-based HTTP client requires PHP to support the fsockopen(),
 * fwrite(), fgets(), fflush() and fclose() functions.
 *
 * This HTTP client only supports the HTTP POST method since that is all that
 * is needed for the Akismet API.
 *
 * @category  Services
 * @package   Services_Akismet
 * @author    Michael Gauthier <mike@silverorange.com>
 * @author    Bret Kuhns
 * @copyright 2007-2008 Bret Kuhns, 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Akismet
 * @link      http://akismet.com/development/api/
 */
class Services_Google_Reader_HttpClient_Socket extends Services_Google_Reader_HttpClient
{
    // {{{ private properties

    /**
     * Akismet API server host name
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Socket::__construct()
     */
    private $_host = '';

    /**
     * TCP/IP Port on which to connect
     *
     * @var integer
     *
     * @see Services_Google_Reader_HttpClient_Socket::__construct()
     */
    private $_port = 80;

    /**
     * HTTP user agent string of this HTTP client
     *
     * @var string
     *
     * @see Services_Google_Reader_HttpClient_Socket::__construct()
     */
    private $_user_agent = '';

    /**
     * Number of bytes to read at once from the HTTP server
     *
     * @var integer
     *
     * @see Services_Google_Reader_HttpClient_Socket::post()
     */
    private $_chunk_size = 4096;

    /**
     * Whether or not this client is connected
     *
     * @var boolean
     *
     * @see Services_Google_Reader_HttpClient_Socket::_connect()
     * @see Services_Google_Reader_HttpClient_Socket::_disconnect()
     */
    private $_connected = false;

    /**
     * The TCP/IP socket connection of this HTTP client
     *
     * @var resource
     *
     * @see Services_Google_Reader_HttpClient_Socket::_connect()
     * @see Services_Google_Reader_HttpClient_Socket::_disconnect()
     */
    private $_connection = null;

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
     *         connecting to the Akismet API server, if there is an error
     *         sending the HTTP request to the Akismet API server, if there
     *         is an error reading the response from the Akismet API server or
     *         if the response from the Akismet API server is invalid.
     */
    public function post($path, $content, $api_key = '')
    {
        $this->_connect();

        if (strlen($api_key) > 0) {
            $host = $api_key . '.' . $this->_host;
        } else {
            $host = $this->_host;
        }

        if (extension_loaded('mbstring') &&
            ini_get('mbstring.func_overload') & 2 == 2) {
            // get byte-length of string if mb_string function overloading is
            // enabled for the strlen function group
            $content_length = mb_strlen($content, '8bit');
        } else {
            $content_length = strlen((binary)$content);
        }

        $request = sprintf("POST %s HTTP/1.1\r\n" .
            "Host: %s\r\n" .
            "Content-Type: application/x-www-form-urlencoded; " .
            "charset=utf-8\r\n" .
            "Content-Length: %s\r\n" .
            "User-Agent: %s\r\n" .
            "\r\n" .
            "%s",
            $path,
            $host,
            $content_length,
            $this->_user_agent,
            $content);

        if (fwrite($this->_connection, $request) === false) {
            throw new Services_Google_Reader_CommunicationException('Unable to ' .
                'send request to API server.');
        }

        if (fflush($this->_connection) === false) {
            throw new Services_Google_Reader_CommunicationException('Unable to ' .
                'send request to API server.');
        }

        $response = '';
        while (!feof($this->_connection)) {
            $chunk = fgets($this->_connection, $this->_chunk_size);
            if ($chunk === false) {
                throw new Services_Google_Reader_CommunicationException('Error ' .
                    'reading response from API server.');
            }
            $response .= $chunk;
        }

        $pos = strpos($response, "\r\n\r\n");
        if ($pos === false) {
            throw new Services_Google_Reader_CommunicationException('Invalid ' .
                'response returned by API server.');
        }
        $response = substr($response, $pos + 4);

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
     * Creates a new TCP/IP socket-based HTTP client for accessing the Akismet
     * REST API
     *
     * Instances of this HTTP client must be instantiated using the
     * {@link Services_Google_Reader_HttpClient::factory()} method.
     *
     * @param string  $host       the Akismet API server host name.
     * @param integer $port       the TCP/IP connection port of this HTTP
     *                            client.
     * @param string  $user_agent the HTTP user agent of this HTTP client.
     */
    protected function __construct($host, $port, $user_agent)
    {
        $this->_host       = strval($host);
        $this->_port       = intval($port);
        $this->_user_agent = strval($user_agent);
    }

    // }}}
    // {{{ _connect()

    /**
     * Connects this HTTP client to the Akismet API server
     *
     * The connection is only performed if this client is disconnected.
     *
     * @return void
     *
     * @throws Services_Google_Reader_CommunicationException if there is an error
     *         connection to the API server. The exception message will contain
     *         an informative string describing the error and the exception
     *         code will contain the error number.
     */
    private function _connect()
    {
        if (!$this->_connected) {
            $this->_connection = fsockopen($this->_host, $this->_port,
                $error_number, $error_text);

            if ($this->_connection === false) {
                throw new Services_Google_Reader_CommunicationException('Unable to ' .
                    'connect to API server: ' . $error_text, $error_number);
            }

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
            // read remaining data and junk it
            while (!feof($this->_connection)) {
                fgets($this->_connection, $this->_chunk_size);
            }
            fclose($this->_connection);
            $this->_connected = false;
        }
    }

    // }}}
}

// }}}
