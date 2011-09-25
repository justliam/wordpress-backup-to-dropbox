<?php
/**
 * HTTP_OAuth_Store_CacheLite 
 * 
 * PHP Version 5.0.0
 * 
 * @uses      HTTP_OAuth_Store_Consumer_Interface
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */

require_once 'HTTP/OAuth/Store/Data.php';
require_once 'HTTP/OAuth/Store/Consumer/Interface.php';
require_once 'Cache/Lite.php';

/**
 * Cache_Lite driver for HTTP_OAuth_Store_Consumer_Interface
 * 
 * @uses      HTTP_OAuth_Store_Consumer_Interface
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */
class HTTP_OAuth_Store_Consumer_CacheLite
implements HTTP_OAuth_Store_Consumer_Interface
{
    const TYPE_REQUEST           = 'requestTokens';
    const TYPE_ACCESS            = 'accessTokens';
    const REQUEST_TOKEN_LIFETIME = 300;

    /**
     * Instance of Cache_Lite
     * 
     * @var Cache_Lite|null
     */
    protected $cache = null;

    /**
     * CacheLite options
     * 
     * @var array
     * @see $defaultOptions
     */
    protected $options = array();

    /**
     * Default options for Cache_Lite
     * 
     * @var array
     */
    protected $defaultOptions = array(
        'cacheDir'             => '/tmp',
        'lifeTime'             => 300,
        'hashedDirectoryLevel' => 2
    );


    /**
     * Instantiate Cache_Lite.  Allows for options to be passed to Cache_Lite.  
     * 
     * @param array $options Options for Cache_Lite constructor
     * 
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->cache   = new Cache_Lite($this->options);
    }

    /**
     * Sets a request token
     * 
     * @param string $token        The request token
     * @param string $tokenSecret  The request token secret
     * @param string $providerName The name of the provider (i.e. 'twitter')
     * @param string $sessionID    A string representing this user's session
     * 
     * @return true on success, false or PEAR_Error on failure
     */
    public function setRequestToken($token, $tokenSecret, $providerName, $sessionID)
    {
        $this->setOptions(self::TYPE_REQUEST, self::REQUEST_TOKEN_LIFETIME);
        $data = array(
            'token'        => $token,
            'tokenSecret'  => $tokenSecret,
            'providerName' => $providerName,
            'sessionID'    => $sessionID
        );

        return $this->cache->save(
            serialize($data),
            $this->getRequestTokenKey($providerName, $sessionID)
        );
    }

    /**
     * Gets a request token as an array of the token, tokenSecret, providerName,
     * and sessionID (array key names)
     * 
     * @param string $providerName The provider name (i.e. 'twitter')
     * @param string $sessionID    A string representing this user's session
     * 
     * @return array on success, false on failure
     */
    public function getRequestToken($providerName, $sessionID)
    {
        $this->setOptions(self::TYPE_REQUEST, self::REQUEST_TOKEN_LIFETIME);
        $result = $this->cache->get(
            $this->getRequestTokenKey($providerName, $sessionID)
        );
        return unserialize($result);
    }

    /**
     * Gets a cache key for request tokens.  It's an md5 hash of the provider name
     * and sessionID
     * 
     * @param string $providerName The provider name (i.e. 'twitter')
     * @param string $sessionID    A string representing this user's session
     * 
     * @return string
     */
    protected function getRequestTokenKey($providerName, $sessionID)
    {
        return md5($providerName . ':' . $sessionID);
    }

    /**
     * Gets access token data in the form of an HTTP_OAuth_Store_Data object
     * 
     * @param string $consumerUserID The end user's ID at the consumer
     * @param string $providerName   The provider name (i.e. 'twitter')
     * 
     * @return HTTP_OAuth_Store_Data
     */
    public function getAccessToken($consumerUserID, $providerName)
    {
        $this->setOptions(self::TYPE_ACCESS);
        $result = $this->cache->get(
            $this->getAccessTokenKey($consumerUserID, $providerName)
        );
        return unserialize($result);
    }

    /**
     * Sets access token data from an HTTP_OAuth_Store_Data object
     * 
     * @param HTTP_OAuth_Store_Data $data The access token data
     * 
     * @return bool true on success, false or PEAR_Error on failure
     */
    public function setAccessToken(HTTP_OAuth_Store_Data $data)
    {
        $this->setOptions(self::TYPE_ACCESS);

        $key = $this->getAccessTokenKey($data->consumerUserID, $data->providerName);
        return $this->cache->save(serialize($data), $key);
    }

    /**
     * Removes an access token
     * 
     * @param HTTP_OAuth_Store_Data $data The access token data
     * 
     * @return bool true on success, false or PEAR_Error on failure
     */
    public function removeAccessToken(HTTP_OAuth_Store_Data $data)
    {
        $this->setOptions(self::TYPE_ACCESS);

        $key = $this->getAccessTokenKey($data->consumerUserID, $data->providerName);
        return $this->cache->remove($key);
    }

    /**
     * Gets an access token key for storage, based on the consumer user ID and the
     * provider name
     * 
     * @param string $consumerUserID The end user's ID at the consumer
     * @param string $providerName   The provider name (i.e. 'twitter')
     * 
     * @return void
     */
    protected function getAccessTokenKey($consumerUserID, $providerName)
    {
        return md5($consumerUserID . ':' . $providerName);
    }

    /**
     * Sets options for Cache_Lite based on the needs of the current method.
     * Options set include the subdirectory to be used, and the expiration.
     * 
     * @param string $key    The sub-directory of the cacheDir
     * @param string $expire The cache lifetime (expire) to be used
     * 
     * @return void
     */
    protected function setOptions($key, $expire = null)
    {
        $cacheDir  = $this->options['cacheDir'] . '/oauth/';
        $cacheDir .= rtrim($key, '/') . '/';

        $this->ensureDirectoryExists($cacheDir);

        $this->cache->setOption('cacheDir', $cacheDir);
        $this->cache->setOption('lifeTime', $expire);
    }

    /**
     * Make sure the given sub directory exists.  If not, create it.
     * 
     * @param string $dir The full path to the sub director we plan to write to
     * 
     * @return void
     */
    protected function ensureDirectoryExists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
?>
