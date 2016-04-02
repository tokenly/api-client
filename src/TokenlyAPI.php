<?php

namespace Tokenly\APIClient;

use Exception;
use Requests;
use Tokenly\APIClient\Exception\APIException;
use Tokenly\HmacAuth\Generator;

class TokenlyAPI
{
    public $client_id     = null;
    public $client_secret = null;
    public $api_base_url  = '';

    function __construct($api_base_url, Generator $authentication_generator=null, $client_id=null, $client_secret=null) {
        $this->api_base_url             = $api_base_url;
        $this->authentication_generator = $authentication_generator;
        $this->client_id                = $client_id;
        $this->client_secret            = $client_secret;
    }
    
    public function get($url, $parameters) {
        return $this->call('GET', $url, $parameters);
    }
    public function getPublic($url, $parameters) {
        return $this->call('GET', $url, $parameters, ['public' => true]);
    }
    public function post($url, $parameters) {
        return $this->call('POST', $url, $parameters);
    }
    public function put($url, $parameters) {
        return $this->call('PUT', $url, $parameters);
    }
    public function patch($url, $parameters) {
        return $this->call('PATCH', $url, $parameters);
    }
    public function delete($url, $parameters) {
        return $this->call('DELETE', $url, $parameters);
    }

    public function call($method, $url, $parameters, $options=[]) {
        $full_url = $this->api_base_url.'/'.rtrim($url, '/');
        return $this->fetchFromAPI($method, $full_url, $parameters, $options);
    }

    // ------------------------------------------------------------------------
    
    protected function fetchFromAPI($method, $url, $parameters=[], $options=[]) {
        $options = array_merge([
            'post_type' => 'json',
        ], $options);

        $headers = [];
        if (!isset($options['public']) OR $options['public'] == false) {
            $headers = $this->buildAuthenticationHeaders($method, $url, $parameters, $headers);
        }

        $request_options = [];

        // build body
        if ($method == 'GET') {
            $request_params = $parameters;
        } else {
            if ($options['post_type'] == 'json') {
                $headers['Content-Type'] = 'application/json';
                $headers['Accept'] = 'application/json';
                if ($parameters) {
                    if($method == 'DELETE'){
                        $request_params = $parameters;
                    }
                    else{
                        $request_params = json_encode($parameters);
                    }
                } else {
                    $request_params = null;
                }
            } else {
                // form fields (x-www-form-urlencoded)
                $request_params = $parameters;
            }
        }

        // send request
        try {
            $response = $this->callRequest($url, $headers, $request_params, $method, $request_options);
        } catch (Exception $e) {
            throw $e;
        }

        // decode json
        try {
            $json = json_decode($response->body, true);
        } catch (Exception $parse_json_exception) {
            // could not parse json
            $json = null;
            throw new APIException("Unexpected response", 1);
        }

        // look for 400 - 500 errors
        $is_bad_status_code = ($response->status_code >= 400 AND $response->status_code < 600);
        $error_message = null;
        $error_code = 1;
        if ($json) {
            // check for error
            if (isset($json['error'])) {
                $error_message = $json['error'];
            } else if (isset($json['errors'])) {
                $error_message = isset($json['message']) ? $json['message'] : (is_array($json['errors']) ? implode(", ", $json['errors']) : $json['errors']);
            }
        }
        if ($is_bad_status_code) {
            if ($error_message === null) {
                $error_message = "Received bad status code: {$response->status_code}";
            }
            $error_code = $response->status_code;
        }

        // for any errors, throw an exception
        if ($error_message !== null) {
            throw new APIException($error_message, $error_code);
        }

        return $json;
    }

    protected function buildAuthenticationHeaders($method, $url, $parameters, $headers=[]) {
        if (!is_null($this->authentication_generator)) {
            $headers = $this->authentication_generator->addSignatureToHeadersArray($method, $url, $parameters, $this->client_id, $this->client_secret, $headers);
        }

        return $headers;
    }

    // for testing
    protected function callRequest($url, $headers, $request_params, $method, $request_options) {
        return Requests::request($url, $headers, $request_params, $method, $request_options);
    }

}
