<?php

use Tokenly\HmacAuth\Generator;
use \PHPUnit_Framework_Assert as PHPUnit;

class AuthenticatedAPITest extends PHPUnit_Framework_TestCase
{

    public function testAuthenticationHeaders() {
        $generator = new Generator();
        $api = Phake::partialMock('Tokenly\APIClient\TokenlyAPI', 'https://127.0.0.1/api/v1', $generator, 'MY_CLIENT_ID', 'MY_CLIENT_SECRET');

        $headers_generated = [];
        Phake::when($api)->callRequest(Phake::anyParameters())->thenReturnCallback(function($url, $headers, $body, $method, $options) use (&$headers_generated) {
            $response = new Requests_Response();
            $response->body = json_encode(['sample' => 'output']);
            $headers_generated = $headers;
            return $response;
        });

        $result = $api->call('GET', 'method/one', ['foo' => 'bar']);
        PHPUnit::assertEquals(['sample' => 'output'], $result);

        // check headers
        PHPUnit::assertNotEmpty($headers_generated);
        $nonce = $headers_generated['X-TOKENLY-AUTH-NONCE'];
        PHPUnit::assertGreaterThanOrEqual(time(), $nonce);
        PHPUnit::assertEquals('MY_CLIENT_ID', $headers_generated['X-TOKENLY-AUTH-API-TOKEN']);
        $expected_signature = $this->expectedSignature($nonce);
        PHPUnit::assertEquals($expected_signature, $headers_generated['X-TOKENLY-AUTH-SIGNATURE']);
    } 

    public function testPublicAPICall() {
        $generator = new Generator();
        $api = Phake::partialMock('Tokenly\APIClient\TokenlyAPI', 'https://127.0.0.1/api/v1', $generator, 'MY_CLIENT_ID', 'MY_CLIENT_SECRET');

        $headers_generated = [];
        Phake::when($api)->callRequest(Phake::anyParameters())->thenReturnCallback(function($url, $headers, $body, $method, $options) use (&$headers_generated) {
            $response = new Requests_Response();
            $response->body = json_encode(['sample' => 'output']);
            $headers_generated = $headers;
            return $response;
        });

        $result = $api->getPublic('method/one', ['foo' => 'bar']);
        PHPUnit::assertEquals(['sample' => 'output'], $result);

        // check headers
        PHPUnit::assertEmpty($headers_generated);
    } 


    // ------------------------------------------------------------------------
    
    protected function expectedSignature($nonce, $url='https://127.0.0.1/api/v1/method/one', $params=['foo' => 'bar']) {
        $str = "GET\n{$url}\n".json_encode((array)$params)."\nMY_CLIENT_ID\n".$nonce;
        return base64_encode(hash_hmac('sha256', $str, 'MY_CLIENT_SECRET', true));
    }


}
