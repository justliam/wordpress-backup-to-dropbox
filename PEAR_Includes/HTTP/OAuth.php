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

/**
 * HTTP_OAuth
 *
 * Main HTTP_OAuth class. Contains helper encoding methods.
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
abstract class HTTP_OAuth
{

    /**
     * Log instances
     *
     * @var array $logs Instances of PEAR Log handlers
     */
    static protected $logs = array();

    /**
     * Attaches an instance of PEAR Log
     *
     * Attached instances of PEAR Log handlers will be logged to
     * through out the use of HTTP_OAuth
     *
     * @param Log $log Instance of a Log
     *
     * @return void
     */
    static public function attachLog(Log $log)
    {
        self::$logs[] = $log;
    }

    /**
     * Detaches an instance of PEAR Log
     *
     * @param Log $detach Instance of PEAR Log to detach
     *
     * @return void
     */
    static public function detachLog(Log $detach)
    {
        foreach (self::$logs as $key => $log) {
            if ($log == $detach) {
                unset(self::$logs[$key]);
            }
        }
    }

    /**
     * Log a message
     *
     * Announces a message to log to all the attached instances of
     * PEAR Log handlers.  Second argument is the method on Log to call.
     *
     * @param string $message Message to announce to Logs
     * @param string $method  Method to log with on Log instances
     *
     * @return void
     */
    public function log($message, $method)
    {
        foreach (self::$logs as $log) {
            $log->$method($message);
        }
    }

    /**
     * Log debug message
     *
     * @param string $message Debug message
     *
     * @return void
     */
    public function debug($message)
    {
        $this->log($message, 'debug');
    }

    /**
     * Log info message
     *
     * @param string $message Info message
     *
     * @return void
     */
    public function info($message)
    {
        $this->log($message, 'info');
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     *
     * @return void
     */
    public function err($message)
    {
        $this->log($message, 'err');
    }

    /**
     * Build HTTP Query
     *
     * @param array $params Name => value array of parameters
     *
     * @return string HTTP query
     */
    static public function buildHttpQuery(array $params)
    {
        if (empty($params)) {
            return '';
        }

        $keys   = self::urlencode(array_keys($params));
        $values = self::urlencode(array_values($params));
        $params = array_combine($keys, $values);

        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $key => $value) {
            $pairs[] =  $key . '=' . $value;
        }

        return implode('&', $pairs);
    }

    /**
     * URL Encode
     *
     * @param mixed $item string or array of items to url encode
     *
     * @return mixed url encoded string or array of strings
     */
    static public function urlencode($item)
    {
        static $search  = array('+', '%7E');
        static $replace = array('%20', '~');

        if (is_array($item)) {
            return array_map(array('HTTP_OAuth', 'urlencode'), $item);
        }

        if (is_scalar($item) === false) {
            return $item;
        }

        return str_replace($search, $replace, rawurlencode($item));
    }

    /**
     * URL Decode
     *
     * @param mixed $item Item to url decode
     *
     * @return string URL decoded string
     */
    static public function urldecode($item)
    {
        if (is_array($item)) {
            return array_map(array('HTTP_OAuth', 'urldecode'), $item);
        }

        return rawurldecode($item);
    }

}

?>
