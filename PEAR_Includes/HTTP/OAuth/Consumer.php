<?php
/**
 * HTTP_OAuth
 *
 * Implementation of the OAuth specification
 *
 * PHP version 5.2.0+
 *
 * LICENSE: This source file is subject to the New BSD license that is
 * available through the world-wide-web at the following URI:
 * http://www.opensource.org/licenses/bsd-license.php. If you did not receive
 * a copy of the New BSD License and are unable to obtain it through the web,
 * please send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */

require_once 'HTTP/OAuth.php';
require_once 'HTTP/OAuth/Consumer/Request.php';
require_once 'HTTP/OAuth/Consumer/Exception/InvalidResponse.php';

/**
 * HTTP_OAuth_Consumer
 *
 * Main consumer class that assists consumers in establishing OAuth
 * creditials and making OAuth requests.
 *
 * <code>
 * $consumer = new HTTP_OAuth_Consumer('key', 'secret');
 * $consumer->getRequestToken('http://example.com/oauth/request_token', $callback);
 *
 * // Store tokens
 * $_SESSION['token']        = $consumer->getToken();
 * $_SESSION['token_secret'] = $consumer->getTokenSecret();
 *
 * $url = $consumer->getAuthorizeUrl('http://example.com/oauth/authorize');
 * http_redirect($url); // function from pecl_http
 *
 * // When they come back via the $callback url
 * $consumer = new HTTP_OAuth_Consumer('key', 'secret', $_SESSION['token'],
 *     $_SESSION['token_secret']);
 * $consumer->getAccessToken('http://example.com/oauth/access_token');
 *
 * // Store tokens
 * $_SESSION['token']        = $consumer->getToken();
 * $_SESSION['token_secret'] = $consumer->getTokenSecret();
 *
 * // $response is an instance of HTTP_OAuth_Consumer_Response
 * $response = $consumer->sendRequest('http://example.com/oauth/protected_resource');
 * </code>
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
class HTTP_OAuth_Consumer extends HTTP_OAuth
{

    /**
     * Consumer key
     *
     * @var string $key Consumer key
     */
    protected $key = null;

    /**
     * secret
     *
     * @var string $secret Consumer secret
     */
    protected $secret = null;

    /**
     * Token
     *
     * @var string Access/Request token
     */
    protected $token = null;

    /**
     * Token secret
     *
     * @var string $tokenSecret Access/Request token secret
     */
    protected $tokenSecret = null;

    /**
     * Signature method
     *
     * @var string $signatureMethod Signature method
     */
    protected $signatureMethod = 'HMAC-SHA1';

    /**
     * Instance of HTTP_OAuth_Consumer_Request
     * 
     * @see accept()
     * @see getOAuthConsumerRequest()
     * @var HTTP_OAuth_Consumer_Request
     */
    protected $consumerRequest = null;

    /**
     * Instance of the last request made
     *
     * @var HTTP_OAuth_Consumer_Request $lastRequest The last request made
     */
    protected $lastRequest = null;

    /**
     * Instance of the last response received
     * 
     * @var HTTP_OAuth_Consumer_Response
     */
    protected $lastResponse =null;

    /**
     * Construct
     *
     * @param string $key         Consumer key
     * @param string $secret      Consumer secret
     * @param string $token       Access/Reqest token
     * @param string $tokenSecret Access/Reqest token secret
     *
     * @return void
     */
    public function __construct($key, $secret, $token = null, $tokenSecret = null)
    {
        $this->key    = $key;
        $this->secret = $secret;
        $this->setToken($token);
        $this->setTokenSecret($tokenSecret);
    }

    /**
     * Get request token
     *
     * @param string $url        Request token url
     * @param string $callback   Callback url
     * @param array  $additional Additional parameters to be in the request
     *                           recommended in the spec.
     * @param string $method     HTTP method to use for the request
     *
     * @return void
     * @throws HTTP_OAuth_Consumer_Exception_InvalidResponse Missing token/secret
     */
    public function getRequestToken($url, $callback = 'oob',
        array $additional = array(), $method = 'POST'
    ) {
        $this->debug('Getting request token from ' . $url);
        $additional['oauth_callback'] = $callback;

        $this->debug('callback: ' . $callback);
        $response = $this->sendRequest($url, $additional, $method);
        $data     = $response->getDataFromBody();
        if (empty($data['oauth_token']) || empty($data['oauth_token_secret'])) {
            throw new HTTP_OAuth_Consumer_Exception_InvalidResponse(
                'Failed getting token and token secret from response', $response
            );
        }

        $this->setToken($data['oauth_token']);
        $this->setTokenSecret($data['oauth_token_secret']);
    }

    /**
     * Get access token
     *
     * @param string $url        Access token url
     * @param string $verifier   OAuth verifier from the provider
     * @param array  $additional Additional parameters to be in the request
     *                           recommended in the spec.
     * @param string $method     HTTP method to use for the request
     *
     * @return array Token and token secret
     * @throws HTTP_OAuth_Consumer_Exception_InvalidResponse Mising token/secret
     */
    public function getAccessToken($url, $verifier = '',
        array $additional = array(), $method = 'POST'
    ) {
        if ($this->getToken() === null || $this->getTokenSecret() === null) {
            throw new HTTP_OAuth_Exception('No token or token_secret');
        }

        $this->debug('Getting access token from ' . $url);
        if ($verifier !== null) {
            $additional['oauth_verifier'] = $verifier;
        }

        $this->debug('verifier: ' . $verifier);
        $response = $this->sendRequest($url, $additional, $method);
        $data     = $response->getDataFromBody();
        if (empty($data['oauth_token']) || empty($data['oauth_token_secret'])) {
            throw new HTTP_OAuth_Consumer_Exception_InvalidResponse(
                'Failed getting token and token secret from response', $response
            );
        }

        $this->setToken($data['oauth_token']);
        $this->setTokenSecret($data['oauth_token_secret']);
    }

    /**
     * Get authorize url
     *
     * @param string $url        Authorize url
     * @param array  $additional Additional parameters for the auth url
     *
     * @return string Authorize url
     */
    public function getAuthorizeUrl($url, array $additional = array())
    {
        $params = array('oauth_token' => $this->getToken());
        $params = array_merge($additional, $params);

        return sprintf('%s?%s', $url, HTTP_OAuth::buildHTTPQuery($params));
    }

    /**
     * Send request
     *
     * @param string $url        URL of the protected resource
     * @param array  $additional Additional parameters
     * @param string $method     HTTP method to use
     *
     * @return HTTP_OAuth_Consumer_Response Instance of a response class
     */
    public function sendRequest($url, array $additional = array(), $method = 'POST')
    {
        $params = array(
            'oauth_consumer_key'     => $this->key,
            'oauth_signature_method' => $this->getSignatureMethod()
        );

        if ($this->getToken()) {
            $params['oauth_token'] = $this->getToken();
        }

        $params = array_merge($additional, $params);

        $req = clone $this->getOAuthConsumerRequest();

        $req->setUrl($url);
        $req->setMethod($method);
        $req->setSecrets($this->getSecrets());
        $req->setParameters($params);
        $this->lastResponse = $req->send();
        $this->lastRequest  = $req;

        return $this->lastResponse;
    }

    /**
     * Get key
     *
     * @return string Consumer key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get secret
     *
     * @return string Consumer secret
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Get token
     *
     * @return string Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param string $token Request/Access token
     *
     * @return void
     */
    public function setToken($token)
    {
        $this->debug('token is now: ' . $token);
        $this->token = $token;
    }

    /**
     * Get token secret
     *
     * @return string Accessoken secret
     */
    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * Set token secret
     *
     * @param string $secret Token secret
     *
     * @return void
     */
    public function setTokenSecret($secret)
    {
        $this->debug('token_secret is now: ' . $secret);
        $this->tokenSecret = $secret;
    }

    /**
     * Get signature method
     *
     * @return string Signature method
     */
    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    /**
     * Set signature method
     *
     * @param string $method Signature method to use
     *
     * @return void
     */
    public function setSignatureMethod($method)
    {
        $this->signatureMethod = $method;
    }

    /**
     * Get secrets
     *
     * @return array Array possible secrets
     */
    public function getSecrets()
    {
        return array($this->secret, (string) $this->tokenSecret);
    }

    /**
     * Accepts a custom instance of HTTP_OAuth_Consumer_Request.
     * 
     * @param HTTP_OAuth_Consumer_Request $object Custom instance
     * 
     * @see getOAuthConsumerRequest()
     * @return void
     */
    public function accept($object)
    {
        $class = get_class($object);
        switch ($class)
        {
        case 'HTTP_OAuth_Consumer_Request':
            $this->consumerRequest = $object;
            break;
        case 'HTTP_Request2':
            $this->getOAuthConsumerRequest()->accept($object);
            break;
        default:
            throw new HTTP_OAuth_Exception('Could not accept: ' . $class);
            break;
        }
    }

    /**
     * Gets instance of HTTP_OAuth_Consumer_Request
     *
     * @see accept()
     * @return HTTP_OAuth_Consumer_Request
     */
    public function getOAuthConsumerRequest()
    {
        if (!$this->consumerRequest instanceof HTTP_OAuth_Consumer_Request) {
            $this->consumerRequest = new HTTP_OAuth_Consumer_Request;
        } 
        return $this->consumerRequest;
    }

    /**
     * Gets the last request
     *
     * @return null|HTTP_OAuth_Consumer_Request Instance of the last request
     * @see self::sendRequest()
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Gets the most recent HTTP_OAuth_Consumer_Response object
     * 
     * @return HTTP_OAuth_Consumer_Response|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}

?>
