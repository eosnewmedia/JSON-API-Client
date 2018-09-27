JSON API Client
===============
[![Build Status](https://travis-ci.org/eosnewmedia/JSON-API-Client.svg?branch=master)](https://travis-ci.org/eosnewmedia/JSON-API-Client) 

Abstract client-side PHP implementation of the [json api specification](http://jsonapi.org/format/).

## Installation

```sh
composer require enm/json-api-client
```

You can use the default HTTP implementation (`Enm\JsonApi\HttpClient\GuzzleAdapter`) which requires the Guzzle client.

```sh
composer require guzzlehttp/guzzle ^6.0
```

or the BuzzCurlAdapter (`Enm\JsonApi\HttpClient\BuzzCurlAdapter`) which requires the `kriswallsmith/buzz` client.

```sh
composer require kriswallsmith/buzz ^0.16
```

If needed you can also implement the interface by yourself to use any HTTP client which supports PSR-7.

## Usage
First you should read the docs at [enm/json-api-common](https://eosnewmedia.github.io/JSON-API-Common/) where all basic structures are defined.

Your API client is an instance of `Enm\JsonApi\Client\JsonApiClient`, which requires a HTTP client (`Enm\JsonApi\HttpClient\HttpClientInterface`) to execute requests.

```php 

$client = new JsonApiClient(
    'http://example.com/api',
    new GuzzleAdapter(new Client()), // with guzzle in this example...
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
