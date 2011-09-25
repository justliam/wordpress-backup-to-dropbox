<?php
/**
 * HTTP_OAuth_Store_Consumer_Interface 
 * 
 * PHP Version 5.0.0
 * 
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */

/**
 * A consumer storage interface for access tokens and request tokens.
 * 
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2010 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */
interface HTTP_OAuth_Store_Consumer_Interface
{
    /**
     * Sets a request token
     * 
     * @param string $token        The request token
     * @param string $tokenSecret  The request token secret
     * @param string $providerName The name of the provider (i.e. 'twitter')
     * @param string $sessionID    A string representing this user's session
     * 
     * @return true on success, false or failure
     */
    public function setRequestToken($token, $tokenSecret, $providerName, $sessionID);

    /**
     * Gets a request token as an array of the token, tokenSecret, providerName,
     * and sessionID (array key names)
     * 
     * @param string $providerName The provider name (i.e. 'twitter')
     * @param string $sessionID    A string representing this user's session
     * 
     * @return array on success, false on failure
     */
    public function getRequestToken($providerName, $sessionID);

    /**
     * Gets access token data in the form of an HTTP_OAuth_Store_Data object
     * 
     * @param string $consumerUserID The end user's ID at the consumer
     * @param string $providerName   The provider name (i.e. 'twitter')
     * 
     * @return HTTP_OAuth_Data_Store
     */
    public function getAccessToken($consumerUserID, $providerName);

    /**
     * Sets access token data from an HTTP_OAuth_Store_Data object
     * 
     * @param HTTP_OAuth_Store_Data $data The access token data
     * 
     * @return bool true on success, false on failure
     */
    public function setAccessToken(HTTP_OAuth_Store_Data $data);

    /**
     * Removes an access token
     * 
     * @param HTTP_OAuth_Store_Data $data The access token data
     * 
     * @return bool true on success, false or PEAR_Error on failure
     */
    public function removeAccessToken(HTTP_OAuth_Store_Data $data);
}
?>
