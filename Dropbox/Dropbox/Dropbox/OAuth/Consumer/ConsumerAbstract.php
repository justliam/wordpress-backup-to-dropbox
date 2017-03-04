<?php

/**
* Abstract OAuth consumer
* @author Ben Tadiar <ben@handcraftedbyben.co.uk>
* @link https://github.com/benthedesigner/dropbox
* @package Dropbox\OAuth
* @subpackage Consumer
*/

abstract class Dropbox_OAuth_Consumer_ConsumerAbstract
{
    // Dropbox web endpoint
    const WEB_URL = 'https://www.dropbox.com/1/';

    // OAuth flow methods
    const REQUEST_TOKEN_METHOD = 'oauth/request_token';
    const AUTHORISE_METHOD = 'oauth/authorize';
    const ACCESS_TOKEN_METHOD = 'oauth/access_token';

    /**
     * Signature method, either PLAINTEXT or HMAC-SHA1
     * @var string
     */
    private $sigMethod = 'PLAINTEXT';

    /**
     * Output file handle
     * @var null|resource
     */
    protected $outFile = null;

    /**
     * Input file handle
     * @var null|resource
     */
    protected $inFile = null;

    /**
     * OAuth token
     * @var stdclass
     */
    private $token = null;

    /**
    * Acquire an unauthorised request token
    * @link http://tools.ietf.org/html/rfc5849#section-2.1
    * @return void
    */
    public function getRequestToken()
    {
        $url = Dropbox_API::API_URL . self::REQUEST_TOKEN_METHOD;
        $response = $this->fetch('POST', $url, '');

        return $this->parseTokenString($response['body']);
    }

    /**
    * Build the user authorisation URL
    * @return string
    */
    public function getAuthoriseUrl()
    {
        // Prepare request parameters
        $params = array(
            'oauth_token' => $this->token->oauth_token,
            'oauth_token_secret' => $this->token->oauth_token_secret,
        );

        // Build the URL and redirect the user
        $query = '?' . http_build_query($params, '', '&');
        $url = self::WEB_URL . self::AUTHORISE_METHOD . $query;

        return $url;
    }

    /**
     * Acquire an access token
     * Tokens acquired at this point should be stored to
     * prevent having to request new tokens for each API call
     * @link http://tools.ietf.org/html/rfc5849#section-2.3
     */
    public function getAccessToken()
    {
        // Get the signed request URL
        $response = $this->fetch('POST', Dropbox_API::API_URL, self::ACCESS_TOKEN_METHOD);

        return $this->parseTokenString($response['body']);
    }

    /**
     * Generate signed request URL
     * See inline comments for description
     * @link http://tools.ietf.org/html/rfc5849#section-3.4
     * @param  string $method     HTTP request method
     * @param  string $url        API endpoint to send the request to
     * @param  string $call       API call to send
     * @param  array  $additional Additional parameters as an associative array
     * @return array
     */
    protected function getSignedRequest($method, $url, $call, array $additional = array())
    {
        // Get the request/access token
        $token = $this->token;
        if (!$token) {
            $token = new stdClass();
            $token->oauth_token = null;
            $token->oauth_token_secret = null;
        }

        // Generate a random string for the request
        $nonce = md5(microtime(true) . uniqid('', true));

        // Prepare the standard request parameters
        $params = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token' => $token->oauth_token,
            'oauth_signature_method' => $this->sigMethod,
            'oauth_version' => '1.0',
            // Generate nonce and timestamp if signature method is HMAC-SHA1
            'oauth_timestamp' => ($this->sigMethod == 'HMAC-SHA1') ? time() : null,
            'oauth_nonce' => ($this->sigMethod == 'HMAC-SHA1') ? $nonce : null,
        );

        // Merge with the additional request parameters
        $params = array_merge($params, $additional);
        ksort($params);

        // URL encode each parameter to RFC3986 for use in the base string
        $encoded = array();
        foreach ($params as $param => $value) {
            if ($value !== null) {
                // If the value is a file upload (prefixed with @), replace it with
                // the destination filename, the file path will be sent in POSTFIELDS
                if (isset($value[0]) && $value[0] === '@') $value = $params['filename'];
                $encoded[] = $this->encode($param) . '=' . $this->encode($value);
            } else {
                unset($params[$param]);
            }
        }

        // Build the first part of the string
        $base = $method . '&' . $this->encode($url . $call) . '&';

        // Re-encode the encoded parameter string and append to $base
        $base .= $this->encode(implode('&', $encoded));

        // Concatenate the secrets with an ampersand
        $key = $this->consumerSecret . '&' . $token->oauth_token_secret;

        // Get the signature string based on signature method
        $signature = $this->getSignature($base, $key);
        $params['oauth_signature'] = $signature;

        // Build the signed request URL
        $query = '?' . http_build_query($params, '', '&');

        return array(
            'url' => $url . $call . $query,
            'postfields' => $params,
        );
    }

    /**
     * Generate the oauth_signature for a request
     * @param string $base Signature base string, used by HMAC-SHA1
     * @param string $key  Concatenated consumer and token secrets
     */
    private function getSignature($base, $key)
    {
        switch ($this->sigMethod) {
            case 'PLAINTEXT':
                $signature = $key;
                break;
            case 'HMAC-SHA1':
                $signature = base64_encode(hash_hmac('sha1', $base, $key, true));
                break;
        }

        return $signature;
    }

    /**
     * Set the token to use for OAuth requests
     * @param stdtclass $token A key secret pair
     */
    public function setToken($token)
    {
        if (!is_object($token))
            throw new Exception('Token is invalid.');

        $this->token = $token;

        return $this;
    }

    public function resetToken()
    {
        $token = new stdClass;
        $token->oauth_token = false;
        $token->oauth_token_secret = false;

        $this->setToken($token);

        return $this;
    }

    /**
     * Set the OAuth signature method
     * @param  string $method Either PLAINTEXT or HMAC-SHA1
     * @return void
     */
    public function setSignatureMethod($method)
    {
        $method = strtoupper($method);

        switch ($method) {
            case 'PLAINTEXT':
            case 'HMAC-SHA1':
                $this->sigMethod = $method;
                break;
            default:
                throw new Exception('Unsupported signature method ' . $method);
        }
    }

    /**
     * Set the output file
     * @param resource Resource to stream response data to
     * @return void
     */
    public function setOutFile($handle)
    {
        if (!is_resource($handle) || get_resource_type($handle) != 'stream') {
            throw new Exception('Outfile must be a stream resource');
        }
        $this->outFile = $handle;
    }

    /**
     * Set the input file
     * @param resource Resource to read data from
     * @return void
     */
    public function setInFile($handle)
    {
        if (!is_resource($handle) || get_resource_type($handle) != 'stream') {
            throw new Exception('Infile must be a stream resource');
        }
        fseek($handle, 0);
        $this->inFile = $handle;
    }

    /**
    * Parse response parameters for a token into an object
    * Dropbox returns tokens in the response parameters, and
    * not a JSON encoded object as per other API requests
    * @link http://oauth.net/core/1.0/#response_parameters
    * @param string $response
    * @return object stdClass
    */
    private function parseTokenString($response)
    {
        if (!$response)
            throw new Exception('Response cannot be null');

        $parts = explode('&', $response);
        $token = new stdClass();
        foreach ($parts as $part) {
            list($k, $v) = explode('=', $part, 2);
            $k = strtolower($k);
            $token->$k = $v;
        }

        return $token;
    }

    /**
     * Encode a value to RFC3986
     * This is a convenience method to decode ~ symbols encoded
     * by rawurldecode. This will encode all characters except
     * the unreserved set, ALPHA, DIGIT, '-', '.', '_', '~'
     * @link http://tools.ietf.org/html/rfc5849#section-3.6
     * @param mixed $value
     */
    private function encode($value)
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}
