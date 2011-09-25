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

require_once 'PEAR/Exception.php';

/**
 * HTTP_OAuth_Exception
 *
 * Main Exception class for HTTP_OAuth. All other HTTP_OAuth Exceptions
 * extend this class.
 *
 * @uses      PEAR_Exception
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
class HTTP_OAuth_Exception extends PEAR_Exception
{

    /**
     * Construct
     *
     * Allow no message to construct a exception
     *
     * @param string                              $message Exception message
     * @param int|Exception|PEAR_Error|array|null $p2      Exception cause
     * @param int|null                            $p3      Exception code or null
     *
     * @return void
     */
    public function __construct($message = '', $p2 = null, $p3 = null)
    {
        parent::__construct($message, $p2, $p3);
    }
}

?>
