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

require_once 'HTTP/Request2.php';
require_once 'HTTP/Request2/Observer/Log.php';
require_once 'HTTP/OAuth/Message.php';
require_once 'HTTP/OAuth/Consumer/Response.php';
require_once 'HTTP/OAuth/Signature.php';
require_once 'HTTP/OAuth/Exception.php';

/**
 * HTTP_OAuth_Consumer_Request
 *
 * Class to make OAuth requests to a provider.  Given a url, consumer secret,
 * token secret, and HTTP method make and sign a request to send.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
class HTTP_OAuth_Consumer_Request extends HTTP_OAuth_Message
{

    /**
     *  Auth type constants
     */
    const AUTH_HEADER = 1;
    const AUTH_POST   = 2;
    const AUTH_GET    = 3;

    /**
     * Auth type
     *
     * @var int $authType Authorization type
     */
    protected $authType = self::AUTH_HEADER;

    /**
     * Secrets
     *
     * Consumer and token secrets that will be used to sign
     * the request
     *
     * @var array $secrets Array of consumer and token secret
     */
    protected $secrets = array('', '');

    /**
     * HTTP_Request2 instance
     *
     * @var HTTP_Request2 $request Instance of HTTP_Request2
     */
    protected $request = null;

    /**
     * Construct
     *
     * Sets url, secrets, and http method
     *
     * @param string $url     Url to be requested
     * @param array  $secrets Array of consumer and token secret
     *
     * @return void
     */
    public function __construct($url = null, array $secrets = array())
    {
        if ($url !== null) {
            $this->setUrl($url);
        }

        if (count($secrets)) {
            $this->setSecrets($secrets);
        }
    }

    /**
     * Accept
     *
     * @param mixed $object Object to accept
     *
     * @see getHTTPRequest2()
     * @return void
     */
    public function accept($object)
    {
        switch (get_class($object)) {
        case 'HTTP_Request2':
            $this->request = $object;
            foreach (self::$logs as $log) {
                $this->request->attach(new HTTP_Request2_Observer_Log($log));
            }
            break;
        default:
            if ($object instanceof Log) {
                HTTP_OAuth::attachLog($object);
                $this->getHTTPRequest2()->attach(
                    new HTTP_Request2_Observer_Log($object)
                );
            }
            break;
        }
    }

    /**
     * Returns $this->request if it is an instance of HTTP_Request.  If not, it 
     * creates one.
     * 
     * @return HTTP_Request2
     */
    protected function getHTTPRequest2()
    {
        if (!$this->request instanceof HTTP_Request2) {
            $this->accept(new HTTP_Request2);
        }
        return $this->request;
    }

    /**
     * Sets consumer/token secrets array
     *
     * @param array $secrets Array of secrets to set
     *
     * @return void
     */
    public function setSecrets(array $secrets = array())
    {
        if (count($secrets) == 1) {
            $secrets[1] = '';
        }

        $this->secrets = $secrets;
    }

    /**
     * Gets secrets
     *
     * @return array Secrets array
     */
    public function getSecrets()
    {
        return $this->secrets;
    }

    /**
     * Sets authentication type
     *
     * Valid auth types are self::AUTH_HEADER, self::AUTH_POST,
     * and self::AUTH_GET
     *
     * @param int $type Auth type defined by this class constants
     *
     * @return void
     */
    public function setAuthType($type)
    {
        static $valid = array(self::AUTH_HEADER, self::AUTH_POST,
            self::AUTH_GET);
        if (!in_array($type, $valid)) {
            throw new InvalidArgumentException('Invalid Auth Type, see class ' .
                'constants');
        }

        $this->authType = $type;
    }

    /**
     * Gets authentication type
     *
     * @return int Set auth type
     */
    public function getAuthType()
    {
        return $this->authType;
    }

    /**
     * Sends request
     *
     * Builds and sends the request. This will sign the request with
     * the given secrets at self::$secrets.
     *
     * @return HTTP_OAuth_Consumer_Response Response instance
     * @throws HTTP_OAuth_Exception when request fails
     */
    public function send()
    {
        $this->buildRequest();
        $request = $this->getHTTPRequest2();

        // Hack for the OAuth's spec + => %20 and HTTP_Request2
        // HTTP_Request2 uses http_build_query() which does spaces
        // as '+' and not '%20'
        $headers     = $request->getHeaders();
        $contentType = isset($headers['content-type'])
                       ? $headers['content-type'] : '';
        if ($this->getMethod() == 'POST'
            && $contentType == 'application/x-www-form-urlencoded'
        ) {

            $body = $this->getHTTPRequest2()->getBody();
            $body = str_replace('+', '%20', $body);
            $this->getHTTPRequest2()->setBody($body);
        }

        try {
            $response = $this->getHTTPRequest2()->send();
        } catch (Exception $e) {
            throw new HTTP_OAuth_Exception($e->getMessage(), $e->getCode());
        }

        return new HTTP_OAuth_Consumer_Response($response);
    }

    /**
     * Builds request for sending
     *
     * Adds timestamp, nonce, signs, and creates the HttpRequest object.
     *
     * @return HttpRequest Instance of the request object ready to send()
     */
    protected function buildRequest()
    {
        $method = $this->getSignatureMethod();
        $this->debug('signing request with: ' . $method);
        $sig = HTTP_OAuth_Signature::factory($this->getSignatureMethod());

        $this->oauth_timestamp = time();
        $this->oauth_nonce     = md5(microtime(true) . rand(1, 999));
        $this->oauth_version   = '1.0';
        $params                = array_merge(
            $this->getParameters(),
            $this->getUrl()->getQueryVariables()
        );
        $this->oauth_signature = $sig->build(
            $this->getMethod(),
            $this->getUrl()->getURL(),
            $params,
            $this->secrets[0],
            $this->secrets[1]
        );

        $params = $this->getOAuthParameters();
        switch ($this->getAuthType()) {
        case self::AUTH_HEADER:
            $auth = $this->getAuthForHeader($params);
            $this->setHeader('Authorization', $auth);
            break;
        case self::AUTH_POST:
            foreach ($params as $name => $value) {
                $this->addPostParameter($name, $value);
            }
            break;
        case self::AUTH_GET:
            break;
        }

        switch ($this->getMethod()) {
        case 'POST':
            foreach ($this->getParameters() as $name => $value) {
                if (substr($name, 0, 6) == 'oauth_') {
                    continue;
                }

                $this->addPostParameter($name, $value);
            }
            break;
        case 'GET':
            $url = $this->getUrl();
            foreach ($this->getParameters() as $name => $value) {
                if (substr($name, 0, 6) == 'oauth_') {
                    continue;
                }

                $url->setQueryVariable($name, $value);
            }

            $this->setUrl($url);
            break;
        default:
            break;
        }
    }

    /**
     * Creates OAuth header
     *
     * Given the passed in OAuth parameters, put them together
     * in a formated string for a Authorization header.
     *
     * @param array $params OAuth parameters
     *
     * @return void
     */
    protected function getAuthForHeader(array $params)
    {
        $url    = $this->getUrl();
        $realm  = $url->getScheme() . '://' . $url->getHost() . '/';
        $header = 'OAuth realm="' . $realm . '"';
        foreach ($params as $name => $value) {
            $header .= ", " . HTTP_OAuth::urlencode($name) . '="' .
                HTTP_OAuth::urlencode($value) . '"';
        }

        return $header;
    }

    /**
     * Call
     *
     * If method exists on HTTP_Request2 pass to that, otherwise
     * throw BadMethodCallException
     *
     * @param string $method Name of the method
     * @param array  $args   Arguments for the method
     *
     * @return mixed Result from method
     * @throws BadMethodCallException When method does not exist on HTTP_Request2
     */
    public function __call($method, $args)
    {
        $httpRequest2 = $this->getHTTPRequest2();

        if (is_callable(array($httpRequest2, $method))) {
            return call_user_func_array(
                array($httpRequest2, $method),
                $args
            );
        }

        throw new BadMethodCallException($method);
    }

}

?>
