<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class APITest extends PHPUnit_Framework_TestCase
{

    public function testAPICall() {
        $api = Phake::partialMock('Tokenly\APIClient\TokenlyAPI', 'https://127.0.0.1/api/v1');
        Phake::when($api)->fetchFromAPI('GET',    'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], [])->thenReturn(['sample' => 'output', 'method' => 'get']);
        Phake::when($api)->fetchFromAPI('GET',    'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], ['public' => true])->thenReturn(['sample' => 'output', 'method' => 'get']);
        Phake::when($api)->fetchFromAPI('POST',   'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], [])->thenReturn(['sample' => 'output', 'method' => 'post']);
        Phake::when($api)->fetchFromAPI('POST',   'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], ['public' => true])->thenReturn(['sample' => 'output', 'method' => 'post']);
        Phake::when($api)->fetchFromAPI('PUT',    'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], [])->thenReturn(['sample' => 'output', 'method' => 'put']);
        Phake::when($api)->fetchFromAPI('PATCH',  'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], [])->thenReturn(['sample' => 'output', 'method' => 'patch']);
        Phake::when($api)->fetchFromAPI('DELETE', 'https://127.0.0.1/api/v1/method/one', ['foo' => 'bar'], [])->thenReturn(['sample' => 'output', 'method' => 'delete']);

        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'get'],    $api->get('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'get'],    $api->getPublic('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'post'],   $api->post('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'post'],   $api->postPublic('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'put'],    $api->put('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'patch'],  $api->patch('method/one', ['foo' => 'bar',]));
        PHPUnit::assertEquals(['sample' => 'output', 'method' => 'delete'], $api->delete('method/one', ['foo' => 'bar',]));
    } 

    public function testRequestForAPICall() {
        $api = Phake::partialMock('Tokenly\APIClient\TokenlyAPI', 'https://127.0.0.1/api/v1');

        Phake::when($api)->callRequest(Phake::anyParameters())->thenReturnCallback(function($url, $headers, $body, $method, $options) {
            $response = new Requests_Response();
            $response->body = json_encode(['sample' => 'output']);
            return $response;
        });


        $result = $api->call('GET', 'method/one', ['foo' => 'bar']);
        PHPUnit::assertEquals(['sample' => 'output'], $result);
    } 


    /**
     * @expectedException        Tokenly\APIClient\Exception\APIException
     * @expectedExceptionMessage Sample error
     */
    public function testErrorAPICall() {
        $api = Phake::partialMock('Tokenly\APIClient\TokenlyAPI', 'https://127.0.0.1/api/v1');
        Phake::when($api)->callRequest(Phake::anyParameters())->thenReturnCallback(function($url, $headers, $body, $method, $options) {
            $response = new Requests_Response();
            $response->body = json_encode(['error' => 'Sample error']);
            return $response;
        });
            
        $api->call('POST', 'method/witherror', ['foo' => 'bar']);
    } 

}
