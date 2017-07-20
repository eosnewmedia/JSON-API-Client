JSON API Client
===============
[![Build Status](https://travis-ci.org/eosnewmedia/JSON-API-Client.svg?branch=master)](https://travis-ci.org/eosnewmedia/JSON-API-Client) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/d6c28b22-fa18-4a74-8204-0e91d205781a/mini.png)](https://insight.sensiolabs.com/projects/d6c28b22-fa18-4a74-8204-0e91d205781a)

Abstract client-side PHP implementation of the [json api specification](http://jsonapi.org/format/), based on the [PSR-7 HTTP message interface](http://www.php-fig.org/psr/psr-7/).

## Installation

```sh
composer require enm/json-api-client
```

## Documentation
First you should read the docs at [enm/json-api-common](https://eosnewmedia.github.io/JSON-API-Common/) where all basic structures are defined.

Your API client for sending requests to a JSON API and get validated responses as JSON API documents is an instance of
`Enm\JsonApi\Client\JsonApiClient`, which requires a HTTP client (`Enm\JsonApi\HttpClient\HttpClientInterface`) to execute
requests.

You can use the default HTTP implementation (`Enm\JsonApi\HttpClient\GuzzleAdapter`) which requires a Guzzle client.

```sh
composer require guzzlehttp/guzzle ^6.0
```

If needed you can also implement the interface by yourself to use any HTTP client which supports PSR-7.

### Usage

| Method                                                                                                 | Return Type             | Description                                                                                                                    |
|--------------------------------------------------------------------------------------------------------|-------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| createJsonApiRequest(string $type, string $id = '')                                                    | JsonApiRequestInterface | Create a new JSON API request object, needed for a delete request.                                                             |
| createFetchRequest(string $type, string $id = '')                                                      | FetchRequestInterface   | Create a new fetch request object, needed for fetch requests.                                                                  |
| createSaveSingleResourceRequest(ResourceInterface $resource, bool $patch = false)                      | SaveRequestInterface    | Create a new save request object, needed for create or patch requests.                                                         |
| fetch(FetchRequestInterface $request)                                                                  | DocumentInterface       | Execute a fetch request for one or many resources and transform the server response into a JSON API document.                  |
| save(SaveRequestInterface $request)                                                                    | DocumentInterface       | Execute a save (create or patch) request and transform the server response into a JSON API document.                           |
| delete(JsonApiRequestInterface $request)                                                               | DocumentInterface       | Execute a delete request and transform the server response into a JSON API document.                                           |
| fetchRelationship(string $relationship, FetchRequestInterface $request, bool $onlyIdentifiers = false) | DocumentInterface       | Execute a fetch request for a relationship and transform the server response into a JSON API document.                         |
| follow(LinkInterface $link, array $headers = [])                                                       | DocumentInterface       | Execute a fetch request which is defined by a JSON API link object and transform the server response into a JSON API document. |


```php 
// configure http client and api endpoint
$guzzle = new Client(); // if you are using guzzle htttp
$baseUri = 'http://example.com/api' // the base uri for all api requests

// create the api client
$apiClient = new JsonApiClient($baseUri, new GuzzleAdapter($guzzle));

// create a fetch request to retrieve a single resource
$request = $apiClient->createFetchRequest('myResourceType', 'myResource');
$request->include('myRelationship'); // include a relationship

// get a response from server
$document = $apiClient->fetch($request);

$myResource = $document->data()->first(); // the resource fetched by this request
$myIncludedResources = $document->included()->all(); // the included resources fetched with the include parameter

##############

// create a fetch request to retrieve all resource of a type
$requestAll =  $apiClient->createFetchRequest('myResourceType');

// get a response from server
$collectionDocument = $apiClient->fetch($request);
$resources = $collectionDocument->data()->all(); // all resources from the current response
```
