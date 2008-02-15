<?php
/**
 * A class to connect to and utilize Google Reader through the API
 *
 * PHP version 5
 * 
 * @category  Default 
 * @package   Services_Google_Reader
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2007 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://pear.unl.edu/
 */
class Services_Google_Reader
{
    private $_username;
    
    private $_password;
    
    private $_token;
    
    private $_api_server      = 'www.google.com';
    
    private $_api_server_port = 80;
    
    /**
     * Client to connect with
     *
     * @var Services_Google_Reader_HTTPClient
     */
    private $_http_client;
    
    const CLIENT  = 'UNL_Services_Google_Reader';
    const VERSION = '0.1.0';
    
    /**
     * Construct a new object to connect to the Google Reader application.
     *
     * @param string $username Username eg: brett.bieber@gmail.com
     * @param string $password Password eg: flibbertygibberty
     */
    function __construct($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;
    }
    
    private function _connect()
    {
        $req =& new HTTP_Request('https://www.google.com/accounts/ClientLogin');
        $req->setMethod(HTTP_REQUEST_METHOD_POST);
        $req->addHeader('Content-type', 'application/x-www-form-urlencoded');
        $req->addPostData('accountType', 'GOOGLE');
        $req->addPostData('Email',       $this->_username);
        $req->addPostData('Passwd',      $this->_password);
        $req->addPostData('source',      self::CLIENT.self::VERSION);
        $req->addPostData('service',     'xapi');
    }
    
    public function getToken()
    {
        $path = '/reader/api/0/token?client='.self::CLIENT;
        $response = $this->_http_client->post($path, $content, $this->_api_key);
        return $response;
    }
    
    /**
     * Add a url to the Google Reader account
     *
     * @param string $url URL to the feed xml file
     */
    public function addFeed($url)
    {
        
    }
    
    /**
     * Sets the HTTP client implementation to use for this Akismet object
     *
     * Available implementations are:
     * - sockets
     * - streams
     * - curl
     *
     * @param string $implementation the name of the HTTP client implementation
     *                               to use. This must be one of the
     *                               implementations specified by
     *                               {@link Services_Akismet_HttpClient}.
     *
     * @throws PEAR_Exception if the specified HTTP client implementation may
     *         not be used with this PHP installation or if the specified HTTP
     *         client implementation does not exist.
     *
     * @see Services_Akismet_HttpClient
     */
    public function setHttpClientImplementation($implementation)
    {

        $user_agent = sprintf('%s/%s',
            self::CLIENT,
            self::VERSION);

        $this->_http_client =
            Services_Google_Reader_HttpClient::factory($this->_api_server,
                $this->_api_port, $user_agent, $implementation);
    }
    
}
