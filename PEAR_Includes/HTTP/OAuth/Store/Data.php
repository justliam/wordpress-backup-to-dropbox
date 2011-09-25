<?php
/**
 * HTTP_OAuth_Store_Data 
 * 
 * PHP Version 5.0.0
 * 
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */

/**
 * A simple structure for storing oauth access token data.
 * 
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pear.php.net/http_oauth
 */
class HTTP_OAuth_Store_Data
{
    /**
     * The user's unique ID at the the consumer
     * 
     * @var mixed
     */
    public $consumerUserID = null;

    /**
     * The user's unique ID at the provider
     * 
     * @var mixed
     */
    public $providerUserID = null;

    /**
     * The name of the provider (i.e. 'twitter')
     * 
     * @var mixed
     */
    public $providerName = null;

    /**
     * The access token
     * 
     * @var string
     */
    public $accessToken = null;

    /**
     * The access token secret
     * 
     * @var string
     */
    public $accessTokenSecret = null;

    /**
     * The token's scope
     * 
     * @var mixed
     */
    public $scope = null;
}
?>
