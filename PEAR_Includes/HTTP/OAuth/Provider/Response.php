<?php
/**
 * HTTP_OAuth
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

require_once 'HTTP/OAuth/Message.php';

/**
 * HTTP_OAuth_Provider_Response
 *
 * @category  HTTP
 * @package   HTTP_OAuth
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/HTTP_OAuth
 * @link      http://github.com/jeffhodsdon/HTTP_OAuth
 */
class HTTP_OAuth_Provider_Response extends HTTP_OAuth_Message
{

    const STATUS_UNSUPPORTED_PARAMETER        = 0;
    const STATUS_UNSUPPORTED_SIGNATURE_METHOD = 1;
    const STATUS_MISSING_REQUIRED_PARAMETER   = 2;
    const STATUS_DUPLICATED_OAUTH_PARAMETER   = 3;

    const STATUS_INVALID_CONSUMER_KEY = 4;
    const STATUS_INVALID_TOKEN        = 5;
    const STATUS_INVALID_SIGNATURE    = 6;
    const STATUS_INVALID_NONCE        = 7;
    const STATUS_INVALID_VERIFIER     = 8;
    const STATUS_INVALID_TIMESTAMP    = 9;

    /**
     * Status map
     *
     * Map of what statuses have codes and body text
     *
     * @var array $statusMap Map of status to code and text
     */
    static protected $statusMap = array(
        self::STATUS_UNSUPPORTED_PARAMETER => array(
            400, 'Unsupported parameter'
        ),
        self::STATUS_UNSUPPORTED_SIGNATURE_METHOD => array(
            400, 'Unsupported signature method'
        ),
        self::STATUS_MISSING_REQUIRED_PARAMETER => array(
            400, 'Missing required parameter'
        ),
        self::STATUS_DUPLICATED_OAUTH_PARAMETER => array(
            400, 'Duplicated OAuth Protocol Parameter'
        ),
        self::STATUS_INVALID_CONSUMER_KEY => array(
            401, 'Invalid Consumer Key'
        ),
        self::STATUS_INVALID_TOKEN => array(
            401, 'Invalid / expired Token'
        ),
        self::STATUS_INVALID_SIGNATURE => array(
            401, 'Invalid signature'
        ),
        self::STATUS_INVALID_NONCE => array(
            401, 'Invalid / used nonce'
        ),
        self::STATUS_INVALID_VERIFIER => array(
            401, 'Invalid verifier'
        ),
        self::STATUS_INVALID_TIMESTAMP => array(
            401, 'Invalid timestamp'
        ),
    );

    /**
     * Headers to be sent the OAuth response
     *
     * @var array $headers Headers to send as an OAuth response
     */
    protected $headers = array();

    /**
     * Body of the response
     *
     * @var string $body Body of the response
     */
    protected $body = '';

    /**
     * Set realm
     *
     * @param string $realm Realm for the WWW-Authenticate header
     *
     * @return void
     */
    public function setRealm($realm)
    {
        $header = 'OAuth realm="' . $realm . '"';
        $this->setHeader('WWW-Authenticate', $header);
    }

    /**
     * Set header
     *
     * @param string $name  Name of the header
     * @param string $value Value of the header
     *
     * @return void
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Get header
     *
     * @param string $name Name of header
     *
     * @return string|null Header if exists, null if not
     */
    public function getHeader($name)
    {
        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }

        return null;
    }

    /**
     * Get all headers
     *
     * @return array Current headers to send
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set all headers
     *
     * @param array $headers Sets all headers to this name/value array
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Set status
     *
     * @param int $status Status constant
     *
     * @return void
     */
    public function setStatus($status)
    {
        if (!array_key_exists($status, self::$statusMap)) {
            throw new HTTP_OAuth_Exception('Invalid status');
        }

        list($code, $text) = self::$statusMap[$status];
        $this->setBody($text);

        if ($this->headersSent()) {
            throw new HTTP_OAuth_Exception('Status already sent');
        }

        switch ($code) {
        case 400:
            $this->header('HTTP/1.1 400 Bad Request');
            break;
        case 401:
            $this->header('HTTP/1.1 401 Unauthorized');
            break;
        }
    }

    // @codeCoverageIgnoreStart
    /**
     * Headers sent
     *
     * @return bool If the headers have been sent
     */
    protected function headersSent()
    {
        return headers_sent();
    }

    /**
     * Header
     *
     * @param string $header Header to add
     *
     * @return void
     */
    protected function header($header)
    {
        return header($header);
    }
    // @codeCoverageIgnoreEnd

    /**
     * Prepare body
     *
     * Sets the body if nesscary
     *
     * @return void
     */
    protected function prepareBody()
    {
        if ($this->headersSent() && $this->getBody() !== '') {
            $this->err('Body already sent, not setting');
        } else {
            $this->setBody(HTTP_OAuth::buildHTTPQuery($this->getParameters()));
        }
    }

    /**
     * Set body
     *
     * @param string $body Sets the body to send
     *
     * @return void
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get body
     *
     * @return string Body that will be sent
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Send response
     *
     * Does a check whether or not headers have been sent in order
     * to determine if it can send them.
     *
     * @return void
     */
    public function send()
    {
        $this->prepareBody();
        if (!$this->headersSent()) {
            $this->header('HTTP/1.1 200 OK');
            foreach ($this->getHeaders() as $name => $value) {
                // @codeCoverageIgnoreStart
                $this->header($name . ': ' . $value);
                // @codeCoverageIgnoreEnd
            }
        } else {
            $this->err('Headers already sent, can not send headers');
        }

        echo $this->getBody();
    }

}

?>
