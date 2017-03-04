<?php
class APITest extends PHPUnit_Framework_TestCase
{
    private $api;

    private function assertValidFile($localFile, $dropboxFile = false)
    {
        if (!$dropboxFile)
            $dropboxFile = basename($localFile);

        $response = $this->api->getFile($dropboxFile);

        $this->assertEquals($dropboxFile, $response['name']);
        $this->assertEquals(
            file_get_contents($localFile),
            $response['data']
        );
    }

    private function cleanUp($file)
    {
        $response = $this->api->delete(basename($file));
        $this->assertEquals(200, $response['code']);
    }

    public function setUp()
    {
        $tokenData = unserialize(file_get_contents('oauth.token'));

        $OAuth = new Dropbox_OAuth_Consumer_Curl($tokenData['consumerKey'], $tokenData['consumerSecret']);
        $OAuth->setToken($tokenData['token']);

        $this->api = new API($OAuth);
    }

    public function testAccountInfo()
    {
        $response = $this->api->accountInfo();
        $this->assertEquals(200, $response['code']);
    }

    public function testPutFile()
    {
        $response = $this->api->putFile(__FILE__);

        $this->assertEquals(200, $response['code']);
        $this->assertValidFile(__FILE__);
        $this->cleanUp(__FILE__);
    }

    public function testPutStream()
    {
        $fh = fopen(__FILE__, 'r');
        $response = $this->api->putStream($fh, 'stream.txt');

        $this->assertEquals(200, $response['code']);
        $this->assertValidFile(__FILE__, 'stream.txt');
        $this->cleanUp('stream.txt');
    }

    public function testChunkedUpload()
    {
        //Ceate a 10MB file to test
        $fh = fopen('bigFile.txt', 'w');
        for ($i = 0; $i < 102400; $i++) {
            fwrite($fh, "..................................................");
            fwrite($fh, "..................................................");
        }
        fclose($fh);

        $response = $this->api->chunkedUpload('bigFile.txt');

        $this->assertEquals(200, $response['code']);
        $this->assertValidFile('bigFile.txt');
        $this->cleanUp('bigFile.txt');

        unlink('bigFile.txt');
    }
}
