<?php

/**
 * Simple Omnisend API v3 wrapper
 * Omnisend API v3 documentation: https://api-docs.omnisend.com/
 * This wrapper: https://github.com/omnisend/php-sdk
 *
 * @author  Omnisend
 * @version 1.1
 */

class Omnisend
{
    private $apiKey;
    private $apiUrl = 'https://api.omnisend.com/v3/';
    private $timeout;
    private $numberOfCurlRepeats = 0;
    private $verifySSL = true;
    private $lastError = array();
    private $useCurl = true;
    private $version = "1.1";

    public function __construct($apiKey, $options = array())
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            if (ini_get('allow_url_fopen')) {
                $this->useCurl = false;
            } else {
                throw new \Exception("Error: cURL is required or allow_url_fopen must be enabled.");
            }
        }
        $this->apiKey = $apiKey;
        $this->timeout = self::getTimeout();

        if (strpos($this->apiKey, '-') === false) {
            throw new \Exception("Invalid Omnisend API key.");
        }

        if (sizeof($options) > 0) {
            if (array_key_exists("verifySSL", $options) && is_bool($options['verifySSL'])) {
                $this->verifySSL = $options['verifySSL'];
            }

            if (array_key_exists("timeout", $options) && is_int($options['timeout'])) {
                $this->timeout = $options['timeout'];
            }
        }
    }

    /**
     * Get last error or false if no error occurred
     *
     * @return  array|false  array with "errror" string and "fields" - array of incorrect fields passed
     */
    public function lastError()
    {
        return $this->lastError ?: false;
    }

    /**
     * GET snippet
     *
     *
     * @return  string   html/javascript snippet
     */

    public function getSnippet()
    {
        return "<script type=\"text/javascript\">
        //OMNISEND-SNIPPET-SOURCE-CODE-V1
        window.omnisend = window.omnisend || [];
        omnisend.push([\"accountID\", \"" . substr($this->apiKey, 0, strpos($this->apiKey, '-')) . "\");
        !function(){var e=document.createElement(\"script\");e.type=\"text/javascript\",e.async=!0,e.src=\"https://omnisrc.com/inshop/launcher.js\";var t=document.getElementsByTagName(\"script\")[0];t.parentNode.insertBefore(e,t)}();
    </script>\n";
    }

    /**
     * Make GET request
     *
     * @param   string $endpoint  endpoint url
     * @param   array  $queryParams    Array of query parameters
     *
     * @return  array|false   false on error, assoc array of API response or empty on success
     */

    public function get($endpoint, $queryParams = array())
    {
        return $this->omnisendApi($endpoint, 'GET', '', $queryParams);
    }

    /**
     * Make an HTTP POST/PUT request - for creating and updating items
     * Use it if you don't know if item exists in Omnisend - this method will try POST
     * and if error occurs - PUT method.
     * Endpoint and fields should be used like in POST
     *
     * @param   string $endpoint  endpoint url
     * @param   array  $fields    Assoc array of arguments
     * @param   array  $queryParams    Array of query parameters
     *
     * @return  array|false   false on error, assoc array of API response or empty on success
     */
    public function push($endpoint, $fields = array(), $queryParams = array())
    {
        $result = $this->post($endpoint, $fields, $queryParams);
        if (!$result && !empty($fields) && !$this->lastError['fields']) {
            $id = "";
            switch (true) {
                case $endpoint == "products" && array_key_exists('productID', $fields):
                    $id = "/" . $fields['productID'];
                    break;
                case $endpoint == "categories" && array_key_exists('categoryID', $fields):
                    $id = "/" . $fields['categoryID'];
                    break;
                case $endpoint == "orders" && array_key_exists('orderID', $fields):
                    $id = "/" . $fields['orderID'];
                    break;
                case $endpoint == "lists" && array_key_exists('listID', $fields):
                    $id = "/" . $fields['listID'];
                    break;
                case $endpoint == "carts" && array_key_exists('cartID', $fields):
                    $id = "/" . $fields['cartID'];
                    break;
                case preg_match('/^carts\/(.*)\/products$/', $endpoint) === 1 && array_key_exists('productID', $fields):
                    $id = "/" . $fields['productID'];
                    break;
                default:
                    return $result;
            }
            if ($id) {
                $result = $this->put($endpoint . $id, $fields, $queryParams);
            }

        }
        return $result;
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param   string $endpoint  endpoint url
     * @param   array  $fields    Assoc array of arguments
     * @param   array  $queryParams    Array of query parameters
     *
     * @return  array|false   false on error, assoc array of API response or empty on success
     */
    public function post($endpoint, $fields = array(), $queryParams = array())
    {
        return $this->omnisendApi($endpoint, 'POST', $fields, $queryParams);
    }

    /**
     * Make an HTTP PATCH request - for performing partial updates
     *
     * @param   string $endpoint  endpoint url
     * @param   array  $fields    Assoc array of arguments
     * @param   array  $queryParams    Array of query parameters
     *
     * @return  array|false   false on error, assoc array of API response or empty on success
     */
    public function patch($endpoint, $fields = array(), $queryParams = array())
    {
        return $this->omnisendApi($endpoint, 'PATCH', $fields, $queryParams);
    }

    /**
     * Make an HTTP PUT request - for creating new items
     *
     * @param   string $endpoint  endpoint url
     * @param   array  $fields    Assoc array of arguments
     * @param   int    $timeout Timeout limit for request in seconds
     * @param   array  $queryParams    Array of query parameters
     *
     * @return  array|false   false on error, assoc array of API response or empty on success
     */
    public function put($endpoint, $fields = array(), $queryParams = array())
    {
        return $this->omnisendApi($endpoint, 'PUT', $fields, $queryParams);
    }

    /**
     * Make DELETE request
     *
     * @param   string $endpoint  endpoint url
     *
     * @return  array|false   false on error, empty on success
     * @param   array  $queryParams    Array of query parameters
     */
    public function delete($endpoint, $queryParams = array())
    {
        return $this->omnisendApi($endpoint, 'DELETE', '', $queryParams);
    }

    private function omnisendApi($endpoint, $method = "POST", $fields = array(), $queryParams = array())
    {
        $this->numberOfCurlRepeats++;
        $this->lastError = array();
        $result = false;
        $error = "";
        $status = 0;
        $data_string = "";

        if (!empty($queryParams)) {
            $link = $this->apiUrl . $endpoint . "?" . http_build_query($queryParams, '', '&');
        } else {
            $link = $this->apiUrl . $endpoint;
        }

        if (!empty($fields)) {
            $data_string = json_encode($fields, JSON_UNESCAPED_SLASHES);
        }
        $headers = array(
            'Content-Type: application/json',
            'X-API-KEY:' . $this->apiKey,
            'PHP-SDK:' . $this->version,
        );

        if ($this->useCurl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Omnisend/PHP-SDK/1.1');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            switch ($method) {
                case "POST":
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    break;
                case "DELETE":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
                case "PATCH":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    break;
                case "PUT":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    break;
            }
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
            } else {
                $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
            curl_close($ch);
        } else {
            $context = stream_context_create(
                array('http' => array(
                    'method' => $method,
                    'header' => "Content-type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Content-Length: " . strlen($data_string) . "\r\n" .
                    "X-API-KEY:" . $this->apiKey . "\r\n",
                    "PHP-SDK:" . $this->version,
                    'content' => $data_string,
                    'timeout' => $this->timeout,
                    'ignore_errors' => true,
                ),
                    'ssl' => array(
                        'verify_peer' => $this->verifySSL,
                        'verify_peer_name' => $this->verifySSL,
                    ),
                )
            );
            $response = @file_get_contents($link, false, $context);
            if (isset($http_response_header)) {
                $status = $this->getHttpCode($http_response_header);
            } else {
                $err = error_get_last();
                $error = $err['message'];
            }

        }

        if (!empty($error)) {
            $this->lastError = array(
                "error" => "Couldn't send request: " . $error,
                "statusCode" => 500,
            );
        } else {
            if ($this->numberOfCurlRepeats == 1 && ($status == 408 || $status == 429 || $status == 503)) {
                $result = self::omnisendApi($link, $endpoint, $fields);
            } elseif ($status >= 200 && $status < 300) {
                if ($response && !empty($response)) {
                    return json_decode($response, true);
                } else {
                    return true;
                }
            } elseif ($status == 403) {
                $result = false;
                $this->lastError = array(
                    'error' => "Forbidden. Incorrect API Key or you don't have rights to access this endpoint.",
                    'statusCode' => $status,
                );
            } elseif ($status == 429) {
                $result = false;
                $this->lastError = array(
                    'error' => "Rate limit reached. Please try again later.",
                    'statusCode' => $status,
                );
            } else {
                $result = false;
                $this->lastError = array(
                    'error' => "Unknow error occured.",
                    'statusCode' => $status ? $status : 500,
                );
                if ($response) {
                    $responseData = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->lastError['error'] = $responseData['error'] ? $responseData['error'] : $this->lastError['error'];
                        $this->lastError['fields'] = !empty($responseData['fields']) ? $responseData['fields'] : null;
                    }
                }
                $this->numberOfCurlRepeats = 0;
            }
        }

        return $result;

    }

    private static function getTimeout()
    {
        $timeout = ini_get('max_execution_time');
        if ($timeout > 10 && $timeout <= 30) {
            $timeout = $timeout - 5;
        } else {
            $timeout = 30;
        }
        return $timeout;
    }

    private function getHttpCode($http_response_header)
    {
        if (is_array($http_response_header)) {
            $parts = explode(' ', $http_response_header[0]);
            if (count($parts) > 1) {
                return intval($parts[1]);
            }
        }
        return 0;
    }

}
