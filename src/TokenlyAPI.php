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

    protected $request_timeout = 30;

    private $auth_generator = null;

    function __construct($api_base_url, Generator $auth_generator=null, $client_id=null, $client_secret=null) {
        $this->api_base_url   = rtrim($api_base_url, '/');;
        $this->client_id      = $client_id;
        $this->client_secret  = $client_secret;

        $this->auth_generator = $auth_generator;
    }

    public function setRequestTimeout($request_timeout)
    {
        $this->request_timeout = $request_timeout;
    }
    
    public function get($url, $parameters=[]) {
        return $this->call('GET', $url, $parameters);
    }
    public function getPublic($url, $parameters=[]) {
        return $this->call('GET', $url, $parameters, ['public' => true]);
    }
    public function post($url, $parameters) {
        return $this->call('POST', $url, $parameters);
    }
    public function postPublic($url, $parameters) {
        return $this->call('POST', $url, $parameters, ['public' => true]);
    }
    public function put($url, $parameters) {
        return $this->call('PUT', $url, $parameters);
    }
    public function patch($url, $parameters) {
        return $this->call('PATCH', $url, $parameters);
    }
    public function delete($url, $parameters=[]) {
        return $this->call('DELETE', $url, $parameters);
    }

    public function call($method, $url, $parameters, $options=[], $request_options=[]) {
        $full_url = $this->api_base_url.'/'.trim($url, '/');
        return $this->fetchFromAPI($method, $full_url, $parameters, $options, $request_options);
    }

    // ------------------------------------------------------------------------
    
    protected function fetchFromAPI($method, $url, $parameters=[], $options=[], $request_options=[]) {
        $options = array_merge([
            'post_type' => 'json',
        ], $options);

        $request_options = array_merge([
            'timeout'   => $this->request_timeout,
        ], $request_options);

        // get the headers and request params
        list($headers, $request_params) = $this->buildRequestHeadersAndParams($method, $url, $parameters, $options);

        // send request
        $response = $this->callRequest($url, $headers, $request_params, $method, $request_options);

        // decode json
        $json = $this->decodeJsonFromResponse($response->body);

        // look for 400 - 500 errors
        $this->checkForErrorsInResponse($response, $json);

        return $json;
    }

    protected function buildRequestHeadersAndParams($method, $url, $parameters, $options) {
        $headers = isset($options['headers']) ? $options['headers'] : [];
        if (!isset($options['public']) OR $options['public'] == false) {
            $headers = $this->buildAuthenticationHeaders($method, $url, $parameters, $headers);
        }

        // build body
        if ($method == 'GET') {
            $request_params = $parameters;
        }

        if ($method != 'GET') {
            // default to form fields (x-www-form-urlencoded)
            $request_params = $parameters;

            if ($options['post_type'] == 'json') {
                // override request params
                $headers['Content-Type'] = 'application/json';
                $headers['Accept'] = 'application/json';
                if ($parameters) {
                    if ($method == 'DELETE'){
                        $request_params = $parameters;
                    }

                    if ($method != 'DELETE'){
                        $request_params = json_encode($parameters);
                    }
                }

                if (!$parameters) {
                    $request_params = null;
                }
            }
        }

        return [$headers, $request_params];

    }

    protected function buildAuthenticationHeaders($method, $url, $parameters, $headers=[]) {
        if (!is_null($this->auth_generator)) {
            $headers = $this->auth_generator->addSignatureToHeadersArray($method, $url, $parameters, $this->client_id, $this->client_secret, $headers);
        }

        return $headers;
    }

    protected function decodeJsonFromResponse($response_body) {
        try {
            $json = json_decode($response_body, true);
        } catch (Exception $parse_json_exception) {
            // could not parse json
            throw new APIException("Unexpected response", 1);
        }

        return $json;
    }

    protected function checkForErrorsInResponse($response, $json) {
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
    }

    // for testing
    protected function callRequest($url, $headers, $request_params, $method, $request_options) {
        return Requests::request($url, $headers, $request_params, $method, $request_options);
    }

}
