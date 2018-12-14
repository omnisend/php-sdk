Omnisend PHP-SDK
=============

Simple Omnisend API v3 wrapper in PHP.

Omnisend API v3 documentation can be found [here](https://api-docs.omnisend.com/).

Requires PHP > 5.3, cURL or ``allow_url_fopen`` to be enabled.

Installation
------------

You can install Omnisend PHP-SDK by either using Composer either by downloading and including it manually.

#### Install using a composer

1. Run these commands:

```
composer require omnisend/php-sdk
composer install
```

2. Add the autoloader to your application:
```php
require_once("vendor/autoload.php");
```
**Note:** check and correct, if needed, "vendor/autoload.php" path.


#### Install manually

Download the `Omnisend.php` file and include it manually:

```php
require_once('Omnisend.php'); 
```
**Note:** check and correct if needed "Omnisend.php" path.

Available methods & options
--------
**Creating instance with your API Key and options (optional)**
```php
$options = array(
    'timeout' => 30,
    'verifySSL' => false
);
$omnisend = new Omnisend('API-KEY', $options);
```

Available options:

|Name|Type|Description|
|---|---|---|
|timeout|int|Timeout. If not passed - will be calculated depending on PHP max_execution_time
|verifySSL|bool|Default - true. Enable (true) or disable (false) SSL verification.

**Available methods**

`endpoint` - endpoint url (ex. 'contacts', 'products/prod123'). See [documentation](https://api-docs.omnisend.com/) for available endpoints.

`queryParams` - array of query parameters

`fields` - array of fields

* getSnippet() - returns html snippet code
* get(endpoint, queryParams) - make `GET` request.
* push(endpoint, fields, queryParams) - makes `POST` request. If error occurs (resource exists in Omnisend) - makes `PUT` request. This method can be used if you don't know if item exists in Omnisend. `queryParams` - optional.
* post(endpoint, fields, queryParams) - make `POST` request. Used to create new item in Omnisend. Will return an error if an item already exists in Omnisend. `queryParams` - optional.
* put(endpoint, fields, queryParams) - make `PUT` request. Used to replace item in Omnisend. Will return an error if an item doesn't exists in Omnisend. `queryParams` - optional.
* patch(endpoint, fields, queryParams) - make `PATCH` request. Used to update item in Omnisend. Will return an error if an item doesn't exists in Omnisend. `queryParams` - optional.
* delete(endpoint, queryParams)  - make `DELETE` request. Used to delete item in Omnisend. Will return an error if an item doesn't exists in Omnisend. `queryParams` - optional.

Responses
--------

Each method will return `false` in case of an error, `array` (see [documentation](https://api-docs.omnisend.com/) for responses) or `true` (for empty body (204) responses) in case of a success.

So you can easily check if a request was successful:
```php
$cart = $omnisend->delete('carts/cart-123');
if ($cart) {
    //request was successful
} else {
    //there was an error
}
```

In case of a failed request, you can get an error description with `lastError()`:
```php
 var_dump($omnisend->lastError());
 ```

Output will be an array with:
* error - error description
* statusCode - HTTP response [status code](https://api-docs.omnisend.com/v3/overview/responses)
* fields - optional - array of missing required, incorrect or incorrectly formatted `fields` (passed with a request)

Example:

```php
array {
  ["error"]=> "2 error(s) found. Check 'fields' array for details."
  ["statusCode"]=> 400
  ["fields"]=>
  array {
    [0]=>
    array {
      [0]=> "cartSum: field required but not found in Json"
    }
    [1]=>
    array {
      [0]=> "currency: field required but not found in Json"
    }
  }
}
```

Examples
--------

1. Create an instance with your API key and options (optional)

```php
$omnisend = new Omnisend('your-api-key');
```

2. Make a request, for example, create a new contact in Omnisend:

```php
$contacts = $omnisend->post(
  'contacts',
   array(
       "email" => "vanessa.kensington@example.com", 
       "firstName" => "Vanessa", 
       "lastName" => "Kensington", 
       "status" => "subscribed", 
       "statusDate" => "2018-12-11T10:29:43+00:00"
    )
);
```
3. Check if a request was successful:

```php
if ($contacts) {
    //request was successful

    //print response
    print_r($contacts); 
    //get contactID from response
    $contactID = $contacts['contactID'];
} else {
    //there was an error
    print_r($omnisend->lastError());
}
```

See more examples in `examples/examples.php`
