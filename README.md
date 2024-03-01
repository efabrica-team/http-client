# efabrica/http-client

**efabrica/http-client** is a PHP package that provides a simple and efficient HTTP client based on Symfony's HttpClient component. 
It adds named arguments in constructor and methods, and provides a more statically analysable API for making HTTP requests.

## Installation

You can install the package using Composer. Run the following command in your project's root directory:

```bash
composer require efabrica/http-client
```

## Usage

### Creating an instance

To create an instance of the `HttpClient` class, use the `create` method. This method allows you to configure various options for the HTTP client, such as base URL, headers, authentication, and more.

```php
use Efabrica\HttpClient\HttpClient;

// Create an instance with default options
$http = HttpClient::create();

// Create an instance with custom options
$http = HttpClient::create(
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

$http = HttpClient::create('https://api.example.com', 'example_llt');

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

### Handling Asynchronous Responses

The `HttpClient` class supports Symfony's asynchronous `ResponseInterface`. This allows you to work with responses asynchronously without blocking until their methods are called.

```php
use Efabrica\HttpClient\HttpClient;

$http = HttpClient::create();

// Send an asynchronous request (does not block)
$response = $http->get('https://api.example.com/resource');

// Access response asynchronously
// > Exceptions are thrown when the response is read
echo $response->getStatusCode(); // int, asynchronous (blocks until response headers are available)
echo $response->getHeaders(); // array, asynchronous (blocks until response headers are available) 
echo $response->getContent(); // string, asynchronous (blocks until response body is available)
$response->toArray(); // JSON body as array, asynchronous (blocks until response body is available)
```

### Streaming Responses

You can use the `stream` method to yield responses chunk by chunk as they complete.

```php
use Efabrica\HttpClient\HttpClient;

$http = HttpClient::create();

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

$http = HttpClient::create();

// Add a decorator to the HTTP client
$http->addDecorator(new CachedHttpClient($cache, $http->getClient()));

// Create a new instance with an additional decorator
$newHttpClient = $http->withDecorator(new CachedHttpClient($cache, $http->getClient()));
```

## Additional Methods

The `HttpClient` class provides additional methods for managing options, cloning instances, and getting the inner client.

- `withOptions`: Create a new instance with updated options.
- `getClient`: Get the inner client, possibly decorated.

## Contributions

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please open an issue or submit a pull request.
