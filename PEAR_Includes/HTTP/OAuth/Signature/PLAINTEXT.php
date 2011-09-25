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

require_once 'HTTP/OAuth/Signature/Common.php';

/**
 * HTTP_OAuth_Signature_PLAINTEXT
 *
 * Signature class for the PLAINTEXT implementation.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
class HTTP_OAuth_Signature_PLAINTEXT extends HTTP_OAuth_Signature_Common
{

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
    public function build($method, $url, array $params, $consumerSecret,
        $tokenSecret = ''
    ) {
        return $this->getKey($consumerSecret, $tokenSecret);
    }

}

?>
