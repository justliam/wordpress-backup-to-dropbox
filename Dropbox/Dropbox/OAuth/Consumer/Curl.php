<?php

/**
* OAuth consumer using PHP cURL
* @author Ben Tadiar <ben@handcraftedbyben.co.uk>
* @link https://github.com/benthedesigner/dropbox
* @package Dropbox\OAuth
* @subpackage Consumer
*/

class Dropbox_OAuth_Consumer_Curl extends Dropbox_OAuth_Consumer_ConsumerAbstract
{
    /**
     * Default cURL options
     * @var array
     */
    protected $defaultOptions = array(
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_VERBOSE        => true,
        CURLOPT_HEADER         => true,
        CURLINFO_HEADER_OUT    => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
    );

    /**
     * Store the last response form the API
     * @var mixed
     */
    protected $lastResponse = null;

   /**
     * Set properties and begin authentication
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret)
    {
        // Check the cURL extension is loaded
        if (!extension_loaded('curl')) {
            throw new Exception('The cURL OAuth consumer requires the cURL extension');
        }

        $this->consumerKey = $key;
        $this->consumerSecret = $secret;
    }

    /**
     * Execute an API call
     * @todo Improve error handling
     * @param  string        $method     The HTTP method
     * @param  string        $url        The API endpoint
     * @param  string        $call       The API method to call
     * @param  array         $additional Additional parameters
     * @return string|object stdClass
     */
    public function fetch($method, $url, $call, $additional = array())
    {
        // Get the signed request URL
        $request = $this->getSignedRequest($method, $url, $call, $additional);

        // Initialise and execute a cURL request
        $handle = curl_init($request['url']);

        // Get the default options array
        $options = $this->defaultOptions;
        $options[CURLOPT_CAINFO] = dirname(__FILE__) . '/ca-bundle.pem';

        if ($method == 'GET' && $this->outFile) { // GET
            $options[CURLOPT_RETURNTRANSFER] = false;
            $options[CURLOPT_HEADER] = false;
            $options[CURLOPT_FILE] = $this->outFile;
            $options[CURLOPT_BINARYTRANSFER] = true;
            $this->outFile = null;
        } elseif ($method == 'POST') { // POST
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $request['postfields'];
        } elseif ($method == 'PUT' && $this->inFile) { // PUT
            $options[CURLOPT_PUT] = true;
            $options[CURLOPT_INFILE] = $this->inFile;
            // @todo Update so the data is not loaded into memory to get its size
            $options[CURLOPT_INFILESIZE] = strlen(stream_get_contents($this->inFile));
            fseek($this->inFile, 0);
            $this->inFile = null;
        }

        // Set the cURL options at once
        curl_setopt_array($handle, $options);

        // Execute and parse the response
        $response = curl_exec($handle);

        //Check if a curl error has occured
        if ($response === false)
            throw new Exception("Error Processing Request: " . curl_error($handle));

        curl_close($handle);

        // Parse the response if it is a string
        if (is_string($response)) {
            $response = $this->parse($response);
        }

        // Set the last response
        $this->lastResponse = $response;

        // Check if an error occurred and throw an Exception
        if (!empty($response['body']->error)) {
            // Dropbox returns error messages inconsistently...
            if ($response['body']->error instanceof stdClass) {
                $array = array_values((array) $response['body']->error);
                $response['body']->error = $array[0];
            }

            // Throw an Exception with the appropriate with the appropriate code
            throw new Exception($response['body']->error, $response['code']);
        }

        return $response;
    }

    /**
     * Parse a cURL response
     * @param  string $response
     * @return array
     */
    private function parse($response)
    {
        // cURL automatically handles Proxy rewrites, remove the "HTTP/1.0 200 Connection established" string
        if (stripos($response, "HTTP/1.0 200 Connection established\r\n\r\n") !== false) {
            $response = str_ireplace("HTTP/1.0 200 Connection established\r\n\r\n", '', $response);
        }

        // Explode the response into headers and body parts (separated by double EOL)
        list($headers, $response) = explode("\r\n\r\n", $response, 2);

        // Explode response headers
        $lines = explode("\r\n", $headers);

        // If the status code is 100, the API server must send a final response
        // We need to explode the response again to get the actual response
        if (preg_match('#^HTTP/1.1 100#', $lines[0])) {
            list($headers, $response) = explode("\r\n\r\n", $response, 2);
            $lines = explode("\r\n", $headers);
        }

        // Get the HTTP response code from the first line
        $first = array_shift($lines);
        $pattern = '#^HTTP/1.1 ([0-9]{3})#';
        preg_match($pattern, $first, $matches);
        $code = $matches[1];

        // Parse the remaining headers into an associative array
        $headers = array();
        foreach ($lines as $line) {
            list($k, $v) = explode(': ', $line, 2);
            $headers[strtolower($k)] = $v;
        }

        // If the response body is not a JSON encoded string
        // we'll return the entire response body
        if (!$body = json_decode($response)) {
            $body = $response;
        }

        return array('code' => $code, 'body' => $body, 'headers' => $headers);
    }

    /**
     * Return the response for the last API request
     * @return mixed
     */
    public function getlastResponse()
    {
        return $this->lastResponse;
    }
}
