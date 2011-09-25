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
 * HTTP_OAuth_Signature_Common
 *
 * Common class for signature implemenations. Holds specification logic to
 * create signature base strings and keys.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
abstract class HTTP_OAuth_Signature_Common extends HTTP_OAuth
{

    /**
     * Get base
     *
     * @param mixed $method HTTP method used in the request
     * @param mixed $url    URL of the request
     * @param array $params Parameters in the request
     *
     * @return string Base signature string
     */
    public function getBase($method, $url, array $params)
    {
        if (array_key_exists('oauth_signature', $params)) {
            unset($params['oauth_signature']);
        }
        
        $croppedUrl = reset(explode('?', $url));
        
        $parts = array($method, $croppedUrl,
            HTTP_OAuth::buildHTTPQuery($params));
        $base  = implode('&', HTTP_OAuth::urlencode($parts));
        $this->debug('Signing with base string: ' . $base);
        return $base;
    }

    /**
     * Get key
     *
     * @param string $consumerSecret Consumer secret value
     * @param string $tokenSecret    Token secret value (if exists)
     *
     * @return string Signature key
     */
    public function getKey($consumerSecret, $tokenSecret = '')
    {
        $secrets = array($consumerSecret, $tokenSecret);
        $key = implode('&', HTTP_OAuth::urlencode($secrets));
        $this->debug('Signing with key: ' . $key);
        return $key;
    }

    /**
     * Build
     *
     * @param string $method         HTTP method used
     * @param string $url            URL of the request
     * @param array  $params         Parameters of the request
     * @param string $consumerSecret Consumer secret value
     * @param string $tokenSecret    Token secret value (if exists)
     *
     * @return string Signature
     */
    abstract public function build($method, $url, array $params,
        $consumerSecret, $tokenSecret = ''
    );
}


