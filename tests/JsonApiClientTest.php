<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Client\JsonApiClient;
use Enm\JsonApi\Client\Tests\Mock\MockClient;
use Enm\JsonApi\Model\Document\DocumentInterface;
use Enm\JsonApi\Model\Request\FetchRequestInterface;
use Enm\JsonApi\Model\Request\JsonApiRequestInterface;
use Enm\JsonApi\Model\Request\SaveRequestInterface;
use Enm\JsonApi\Model\Resource\JsonResource;
use Enm\JsonApi\Model\Resource\Link\Link;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class JsonApiClientTest extends TestCase
{
    public function testFetchResource()
    {
        $httpClient = new MockClient(
            [
                'data' => [
                    'type' => 'test',
                    'id' => 'test-1'
                ]
            ]
        );
        $client = new JsonApiClient(
            'http://example.com',
            $httpClient
        );

        $response = $client->fetch($client->fetchRequest('tests', 'test-1'));

        self::assertEquals(200, $response->httpStatus());
        self::assertEquals('test', $response->data()->first()->type());
        self::assertEquals('test-1', $response->data()->first()->id());

        self::assertEquals((string)$httpClient->getUri(), 'http://example.com/tests/test-1');
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\HttpException
     * @expectedExceptionMessage Resource not found!
     */
    public function testFetchResourceNotFound()
    {
        $httpClient = new MockClient(
            [
                'errors' => [
                    ['title' => 'Resource not found!']
                ]
            ],
            404
        );

        $client = new JsonApiClient(
            'http://example.com',
            $httpClient
        );

        $client->fetch($client->fetchRequest('tests', 'test-1'));
    }

    public function testFetchResources()
    {
        $httpClient = new MockClient(['data' => []]);
        $client = new JsonApiClient('http://example.com', $httpClient);

        self::assertTrue($client->fetch($client->fetchRequest('tests'))->shouldBeHandledAsCollection());
        self::assertEquals((string)$httpClient->getUri(), 'http://example.com/tests');
    }

    public function testFetchRelationship()
    {
        $client = new JsonApiClient('http://example.com', new MockClient(['data' => []]));

        self::assertTrue(
            $client->fetchRelationship(
                'examples',
                $client->fetchRequest('test', 'test-1')
            )->shouldBeHandledAsCollection()
        );
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\BadRequestException
     */
    public function testFetchRelationshipMissingResourceId()
    {
        $client = new JsonApiClient('http://example.com', new MockClient([]));

        $client->fetchRelationship('examples', $client->fetchRequest('test'));
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\UnsupportedMediaTypeException
     */
    public function testFetchInvalidContentType()
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->createMock(HttpClientInterface::class);
        $client = new JsonApiClient('http://example.com', $httpClient);
        $client->fetch($client->fetchRequest('tests', 'test-1'));
    }

    public function testCreate()
    {
        $client = new JsonApiClient('http://example.com', new MockClient(null, 204));

        $response = $client->save($client->saveRequest($client->resource('test', '')));
        self::assertEquals(204, $response->httpStatus());
    }

    public function testPatch()
    {
        $client = new JsonApiClient('http://example.com', new MockClient(null, 204));

        $response = $client->save($client->saveRequest($client->resource('test', 'abc'), true));
        self::assertEquals(204, $response->httpStatus());
    }

    public function testDelete()
    {
        $client = new JsonApiClient('http://example.com', new MockClient(null, 204));

        $response = $client->delete($client->jsonApiRequest('test', 'test-1'));
        self::assertEquals(204, $response->httpStatus());
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\BadRequestException
     */
    public function testDeleteWithoutId()
    {
        $client = new JsonApiClient('http://example.com', new MockClient());
        $client->delete($client->jsonApiRequest('test'));
    }

    public function testFetchWithQuery()
    {
        $httpClient = new MockClient();
        $client = new JsonApiClient(
            'http://example.com?filter[status]=new',
            $httpClient
        );
        $request = $client->fetchRequest('tests', 'test-1');
        $request->filter()->set('test', 'example');
        $request->include('example');
        $request->include('example.abc');
        $request->field('example', 'testA');
        $request->field('example', 'testB');
        $request->sorting()->set('lorem', FetchRequestInterface::ORDER_ASC);
        $request->sorting()->set('ipsum', FetchRequestInterface::ORDER_DESC);
        $request->pagination()->set('offset', 0);

        $client->fetch($request);
        parse_str($httpClient->getUri()->getQuery(), $query);

        self::assertArraySubset(
            [
                'include' => 'example,example.abc',
                'fields' => [
                    'example' => 'testA,testB'
                ],
                'filter' => [
                    'status' => 'new',
                    'test' => 'example'
                ],
                'page' => ['offset' => 0],
                'sort' => 'lorem,-ipsum',
            ],
            $query
        );
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\JsonApiException
     * @expectedExceptionCode JSON_ERROR_SYNTAX
     */
    public function testFetchInvalidJsonResponse()
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->createConfiguredMock(
            HttpClientInterface::class,
            [
                'get' => $this->createConfiguredMock(
                    ResponseInterface::class,
                    [
                        'getHeader' => ['application/vnd.api+json'],
                        'getBody' => 'Hallo Welt'
                    ]
                )
            ]
        );

        $client = new JsonApiClient('http://example.com', $httpClient);

        $client->fetch($client->fetchRequest('tests', 'test-1'));
    }

    /**
     * @expectedException \Enm\JsonApi\Exception\HttpException
     * @expectedExceptionMessage Not Found
     */
    public function testFetchNotFoundWithoutJsonError()
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->createConfiguredMock(
            HttpClientInterface::class,
            [
                'get' => $this->createConfiguredMock(
                    ResponseInterface::class,
                    [
                        'getHeader' => ['application/vnd.api+json'],
                        'getStatusCode' => 404,
                        'getReasonPhrase' => 'Not Found'
                    ]
                )
            ]
        );

        $client = new JsonApiClient('http://example.com', $httpClient);

        $client->fetch($client->fetchRequest('tests', 'test-1'));
    }

    public function testFollow()
    {
        $client = new JsonApiClient('http://example.com', new MockClient());
        self::assertInstanceOf(
            DocumentInterface::class,
            $client->follow(new Link('test', 'http://example.com/tests'))
        );
    }

    public function testJsonApiRequest()
    {
        $client = new JsonApiClient('http://example.com', new MockClient());
        self::assertInstanceOf(JsonApiRequestInterface::class, $client->jsonApiRequest('test'));
    }

    public function testFetchRequest()
    {
        $client = new JsonApiClient('http://example.com', new MockClient());
        self::assertInstanceOf(FetchRequestInterface::class, $client->fetchRequest('test'));
    }

    public function testSaveRequest()
    {
        $client = new JsonApiClient('http://example.com', new MockClient());
        self::assertInstanceOf(
            SaveRequestInterface::class,
            $client->saveRequest(new JsonResource('test', 'test'))
        );
    }
}
