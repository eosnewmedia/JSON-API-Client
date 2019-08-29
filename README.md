JSON API Client
===============
[![Build Status](https://travis-ci.org/eosnewmedia/JSON-API-Client.svg?branch=master)](https://travis-ci.org/eosnewmedia/JSON-API-Client) 

Abstract client-side PHP implementation of the [json api specification](http://jsonapi.org/format/).

## Installation

```sh
composer require enm/json-api-client
```

It's recommended to install `kriswallsmith/buzz` as http-client and `nyholm/psr7` for http factories.

```sh
composer require kriswallsmith/buzz nyholm/psr7
```

You can also use any HTTP client which implements [PSR-18](https://www.php-fig.org/psr/psr-18/).

## Usage
First you should read the docs at [enm/json-api-common](https://eosnewmedia.github.io/JSON-API-Common/) where all basic structures are defined.

Your API client is an instance of `Enm\JsonApi\Client\JsonApiClient`, which requires a PSR-18 HTTP client (`Psr\Http\Client\ClientInterface`) to execute requests.

```php 

$client = new JsonApiClient(
    'http://example.com/api',
    $httpClient, // instance of Psr\Http\Client\ClientInterface
    $uriFactory, // instance of Psr\Http\Message\UriFactoryInterface
    $requestFactory, // instance of Psr\Http\Message\RequestFactoryInterface
    $streamFactory, // instance of Psr\Http\Message\StreamFactoryInterface
    new Serializer(),
    new Deserializer()
);

$request = $client->createGetRequest(new Uri('/myResources/1')); // will fetch the resource at http://example.com/api/myResources/1
$request->requestInclude('myRelationship'); // include a relationship

$response = $client->execute($request);

$document = $response->document();
$myResource = $document->data()->first(); // the resource fetched by this request
$myIncludedResources = $document->included()->all(); // the included resources fetched with the include parameter

```
