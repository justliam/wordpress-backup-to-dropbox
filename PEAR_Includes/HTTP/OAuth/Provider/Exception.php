<?php

/**
 * A package for writing/managing OAuth provider requests
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
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 2009 Joe Stump <joe@joestump.net>
 * @license   http://tinyurl.com/new-bsd New BSD License
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/joestump/HTTP_OAuth
 */

require_once 'HTTP/OAuth/Exception.php';

/**
 * Base exception class for all provider exceptions
 *
 * @category HTTP
 * @package  HTTP_OAuth
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://tinyurl.com/new-bsd New BSD License
 * @link     http://pear.php.net/package/HTTP_OAuth
 */
class HTTP_OAuth_Provider_Exception extends HTTP_OAuth_Exception
{

}

?>
