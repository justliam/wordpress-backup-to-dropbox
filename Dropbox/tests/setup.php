<?php

/**
* This setup script will acquire and store an
* access token which can be used by the unit test suite
* @link https://github.com/BenTheDesigner/Dropbox/tree/master/tests
*/

// Require the bootstrap
require_once 'bootstrap.php';

// Check if a token is already stored
if (!file_exists('oauth.token')) {
    echo 'Running Dropbox Test Suite Setup...' . PHP_EOL;

    while (empty($consumerKey)) {
        echo 'Please enter your consumer key: ';
        $consumerKey = trim(fgets(STDIN));
    }

    while (empty($consumerSecret)) {
        echo 'Please enter your consumer secret: ';
        $consumerSecret = trim(fgets(STDIN));
    }

    try {
        // Set up the OAuth consumer
        $OAuth = new Dropbox_OAuth_Consumer_Curl($consumerKey, $consumerSecret);

        $token = $OAuth->getRequestToken();

        $OAuth->setToken($token);

        // Generate the authorisation URL and prompt user
        echo "Generating Authorisation URL...\r\n\r\n";
        echo "===== Begin Authorisation URL =====\r\n";
        echo $OAuth->getAuthoriseUrl() . PHP_EOL;
        echo "===== End Authorisation URL =====\r\n\r\n";
        echo "Visit the URL above and allow the SDK to connect to your account\r\n";
        echo "Press any key once you have completed this step...";
        fgets(STDIN);

        // Acquire the access token
        echo "Acquiring access token...\r\n";

        $accessToken = $OAuth->getAccessToken();
        $token = serialize(array(
            'token' => $accessToken,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
        ));

        // Write the access token to disk
        if (@file_put_contents('oauth.token', $token) === false) {
            throw new Exception('Unable to write token to file');
        } else {
            echo 'Setup complete! Running the test suite.';
        }
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
        exit('Setup failed! Please try running setup again.');
    }
}
