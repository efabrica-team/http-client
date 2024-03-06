# efabrica/http-client

**efabrica/http-client** is a PHP package that provides a simple and efficient HTTP client based on Symfony's HttpClient component. 
It adds named arguments in constructor and methods, and provides a more statically analysable API for making HTTP requests.
It also integrates Nette's Cache and Debugger.

## Installation

You can install the package using Composer. Run the following command in your project's root directory:

```bash
composer require efabrica/http-client
```

> ⚠️ PHP >=8.1 is required.

## Usage

### Creating an instance

To create an instance of the `HttpClient` class, use the `create` method. This method allows you to configure various options for the HTTP client, such as base URL, headers, authentication, and more.

```php
use Efabrica\HttpClient\HttpClient;

// Create an instance with default options
$http = new HttpClient();

// Create an instance with custom options
$http = new HttpClient(
    baseUrl: 'https://api.example.com',
    bearerToken: 'your-access-token',
    timeout: 10.0,
    // ... other options
);
```

### Making Requests

The `HttpClient` class provides methods for making different types of HTTP requests: GET, POST, PUT, PATCH, DELETE.

```php
use Efabrica\HttpClient\HttpClient;

$http = new HttpClient('https://api.example.com', 'example_llt');

// Send a GET request
$response = $http->get('/resource', ['offset' => 0, 'limit' => 10]);

// Send a POST request with JSON payload
$response = $http->post('/resource', json: ['key' => 'value']);

// Send a POST request with FormData payload
$response = $http->post('/resource', body: ['key' => 'value']);

// Send a PUT request
$response = $http->put('https://api.example2.com/resource', ['email' => 'admin@example.com']);

// ... other request methods
```

To be more specific, this is how the `post` method looks like:

```php
/**
 * @param string $url The URL to which the request should be sent.
 * @param array|null $query An associative array of query string values to merge with the request's URL.
 * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
 * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
 * @param iterable|null $headers Headers names provided as keys or as part of values.
 * @param float|null $timeout The idle timeout (in seconds) for the request.
 * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
 * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
 * @param Closure|null $onProgress A callable function to track the progress of the request.
 * @param array|null $extra Additional options for the request
 */
public function post(
    string $url,
    ?array $query = null,
    ?array $json = null,
    mixed $body = null,
    ?iterable $headers = null,
    ?float $timeout = null,
    ?float $maxDuration = null,
    mixed $userData = null,
    ?Closure $onProgress = null,
    ?array $extra = null,
): ResponseInterface
```

### Handling Asynchronous Responses

The `HttpClient` class returns Symfony's asynchronous `ResponseInterface`. 
This allows you to work with responses asynchronously without blocking until their methods are called.

```php
use Efabrica\HttpClient\HttpClient;

$http = new HttpClient();

// Send an asynchronous request (does not block)
$response = $http->get('https://api.example.com/resource');

// Access response asynchronously
// > Exceptions are thrown when the response is read
$response->getStatusCode(); // int          (blocks until response headers are available)
$response->getHeaders(); // array           (blocks until response headers are available) 
$response->getContent(); // string          (blocks until response body is available)
$response->toArray(); // JSON body as array (blocks until response body is available)
```
### Streaming Responses

You can use the `stream` method to yield responses chunk by chunk as they complete.

```php
use Efabrica\HttpClient\HttpClient;

$http = new HttpClient();

// Send multiple requests and stream responses
$responses = [
    $http->get('https://api.example.com/resource1'),
    $http->get('https://api.example.com/resource2'),
    // ... add more requests
];

$stream = $http->stream($responses);

foreach ($stream as $response) {
    // Process each response asynchronously
}
```

### Adding Decorators

You can enhance the functionality of the `HttpClient` by adding decorators. Decorators can modify or extend the behavior of the underlying HTTP client.

```php
use Efabrica\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Decorator\CachedHttpClient;

$http = new HttpClient();

// Add a decorator to the HTTP client
$http->addDecorator(new CachedHttpClient($cache, $http->getClient()));

// Create a new instance with an additional decorator
$newHttpClient = $http->withDecorator(new CachedHttpClient($cache, $http->getClient()));
```

### Updating Options

```php
use Efabrica\HttpClient\HttpClient;

// Create an instance with default options
$http = new HttpClient();

// Create a new instance with updated options
$newHttp = $http->withOptions(baseUrl: 'https://api.new-example.com', /* ... */);

// Now $newHttp is a new instance of HttpClient with the updated options
```

This example shows how to create a new instance of `HttpClient` with updated options using the `withOptions` method. 
The original `HttpClient` instance remains unchanged.

## Contributions

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please open an issue or submit a pull request.
