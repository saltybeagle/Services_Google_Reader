<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Abstract class definition for HTTP client for Google Reader API
 *
 * This abstract class has a factory method to create an instance using a
 * particular implementation. For example:
 *
 * <code>
 * <?php
 * // creates a streams-based http client for use with the Google Reader package
 * $client = Services_Google_Reader_HttpClient::factory('streams',
 *     'rest.akismet.com', 80, 'Services_Google_Reader');
 * ?>
 * </code>
 *
 * The available implementations are:
 * - sockets (used by default)
 * - streams
 * - curl
 *
 * Services_Google_Reader
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2007-2008 Bret Kuhns, silverorange
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
 * @author    Bret Kuhns
 * @copyright 2007-2008 Bret Kuhns, 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Google_Reader
 * @link      http://akismet.com/development/api/
 */

/**
 * Base PEAR exception class.
 */
require_once 'PEAR/Exception.php';

// {{{ class Services_Google_Reader_HttpClient

/**
 * Abstract simple HTTP client for accessing the Akismet REST API
 *
 * This class contains a factory method for creating instances using a
 * particular implementation. For example:
 *
 * <code>
 * <?php
 * // creates a streams-based http client for use with the Akismet package
 * $client = Services_Google_Reader_HttpClient::factory('streams',
 *     'rest.akismet.com', 80, 'Services_Google_Reader');
 * ?>
 * </code>
 *
 * The available implementations are:
 * - sockets (default)
 * - streams
 * - curl
 *
 * This HTTP client only supports the HTTP POST method since that is all that
 * is needed for the Akismet API.
 *
 * @category  Services
 * @package   Services_Google_Reader
 * @author    Michael Gauthier <mike@silverorange.com>
 * @author    Bret Kuhns
 * @copyright 2007-2008 Bret Kuhns, 2008 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Services_Google_Reader
 * @link      http://akismet.com/development/api/
 */
abstract class Services_Google_Reader_HttpClient
{
    // {{{ factory()

    /**
     * Factory method to instantiate a HTTP client implementation
     *
     * @param string  $host           the Akismet API server host name.
     * @param integer $port           the TCP/IP connection port of the HTTP
     *                                client.
     * @param string  $user_agent     the HTTP user agent of the HTTP client.
     * @param string  $implementation optional. The name of the implementation
     *                                to instantiate. Must be one of 'sockets',
     *                                'streams' or 'curl'. If not specified,
     *                                defaults to 'sockets'.
     *
     * @return Services_Google_Reader_HttpClient the instantiated HTTP client
     *         implementation.
     *
     * @throws PEAR_Exception if the implementation is not supported by the
     *         current PHP installation or if the provided implementation does
     *         not exist.
     */
    public static function factory($host, $port, $user_agent,
        $implementation = 'sockets')
    {
        $drivers = array(
            'sockets' => 'Socket',
            'streams' => 'Stream',
            'curl'    => 'Curl'
        );

        if (!array_key_exists($implementation, $drivers)) {
            throw new Exception('Services_Google_Reader_HttpClient ' .
                'implementation "' . $implementation. '" does not exist.');
        }

        $filename = 'Services/Google/Reader/HttpClient/' .
            $drivers[$implementation] . '.php';

        include_once $filename;

        $class_name = 'Services_Google_Reader_HttpClient_' .
            $drivers[$implementation];

        $object = new $class_name($host, $port, $user_agent);

        return $object;
    }

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
     *         communicating with the Akismet API server.
     */
    abstract public function post($path, $content, $api_key = '');

    // }}}
    // {{{ __construct()

    /**
     * Creates a new HTTP client for accessing the Akismet REST API
     *
     * Instances of this HTTP client must be instantiated using the
     * {@link Services_Google_Reader_HttpClient::factory()} method.
     *
     * @param string  $host       the Akismet API server host name.
     * @param integer $port       the TCP/IP connection port of this HTTP
     *                            client.
     * @param string  $user_agent the HTTP user agent of this HTTP client.
     *
     * @throws PEAR_Exception if the implementation is not supported by the
     *         current PHP installation.
     */
    abstract protected function __construct($host, $port, $user_agent);

    // }}}
}

// }}}

?>
