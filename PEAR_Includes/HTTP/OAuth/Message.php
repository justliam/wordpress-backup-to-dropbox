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

/**
 * HTTP_OAuth_Message
 *
 * Main message class for Request and Response classes to extend from.  Provider
 * and Consumer packages use this class as there parent for the request/response
 * classes. This contains specification parameters handling and ArrayAccess,
 * Countable, IteratorAggregate features.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
abstract class HTTP_OAuth_Message
extends HTTP_OAuth
implements ArrayAccess, Countable, IteratorAggregate
{

    /**
     * OAuth Parameters
     *
     * @var string $oauthParams OAuth parameters
     */
    static protected $oauthParams = array(
        'consumer_key',
        'token',
        'token_secret',
        'signature_method',
        'signature',
        'timestamp',
        'nonce',
        'verifier',
        'version',
        'callback',
        'session_handle'
    );

    /**
     * Parameters
     *
     * @var array $parameters Parameters
     */
    protected $parameters = array();

    /**
     * Get OAuth specific parameters
     *
     * @return array OAuth specific parameters
     */
    public function getOAuthParameters()
    {
        $params = array();
        foreach (self::$oauthParams as $param) {
            if ($this->$param !== null) {
                $params[$this->prefixParameter($param)] = $this->$param;
            }
        }

        ksort($params);

        return $params;
    }

    /**
     * Get parameters
     *
     * @return array Request's parameters
     */
    public function getParameters()
    {
        $params = $this->parameters;
        ksort($params);

        return $params;
    }

    /**
     * Set parameters
     *
     * @param array $params Name => value pair array of parameters
     *
     * @return void
     */
    public function setParameters(array $params)
    {
        foreach ($params as $name => $value) {
            $this->parameters[$this->prefixParameter($name)] = $value;
        }
    }

    /**
     * Get signature method
     *
     * @return string Signature method
     */
    public function getSignatureMethod()
    {
        if ($this->oauth_signature_method !== null) {
            return $this->oauth_signature_method;
        }

        return 'HMAC-SHA1';
    }

    /**
     * Get
     *
     * @param string $var Variable to get
     *
     * @return mixed Parameter if exists, else null
     */
    public function __get($var)
    {
        $var = $this->prefixParameter($var);
        if (array_key_exists($var, $this->parameters)) {
            return $this->parameters[$var];
        }

        $method = 'get' . ucfirst($var);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return null;
    }

    /**
     * Set
     *
     * @param string $var Name of the variable
     * @param mixed  $val Value of the variable
     *
     * @return void
     */
    public function __set($var, $val)
    {
        $this->parameters[$this->prefixParameter($var)] = $val;
    }

    /**
     * Offset exists
     *
     * @param string $offset Name of the offset
     *
     * @return bool Offset exists or not
     */
    public function offsetExists($offset)
    {
        return isset($this->parameters[$this->prefixParameter($offset)]);
    }

    /**
     * Offset get
     *
     * @param string $offset Name of the offset
     *
     * @return string Offset value
     */
    public function offsetGet($offset)
    {
        return $this->parameters[$this->prefixParameter($offset)];
    }

    /**
     * Offset set
     *
     * @param string $offset Name of the offset
     * @param string $value  Value of the offset
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->parameters[$this->prefixParameter($offset)] = $value;
    }

    /**
     * Offset unset
     *
     * @param string $offset Name of the offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->parameters[$this->prefixParameter($offset)]);
    }

    /**
     * Count
     *
     * @return int Amount of parameters
     */
    public function count()
    {
        return count($this->parameters);
    }

    /**
     * Get iterator
     *
     * @return ArrayIterator Iterator for self::$parameters
     */
    public function getIterator()
    {
        return new ArrayIterator($this->parameters);
    }

    /**
     * Prefix parameter
     *
     * Prefixes a parameter name with oauth_ if it is a valid oauth paramter
     *
     * @param string $param Name of the parameter
     *
     * @return string Prefix parameter
     */
    protected function prefixParameter($param)
    {
        if (in_array($param, self::$oauthParams)) {
            $param = 'oauth_' . $param;
        }

        return $param;
    }

}

?>
