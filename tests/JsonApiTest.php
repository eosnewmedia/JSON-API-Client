<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Client\JsonApiClient;
use Enm\JsonApi\Serializer\DocumentDeserializerInterface;
use Enm\JsonApi\Serializer\DocumentSerializerInterface;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class JsonApiTest extends TestCase
{
    /**
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function testCreateGetRequestWithResource(): void
    {
        $client = $this->createClient('http://example.com/api');

        $request = $client->createGetRequest(new Uri('/myResources/1'));

        self::assertEquals(
            'http://example.com/api/myResources/1',
            $request->uri()->__toString()
        );
    }

    /**
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function testCreateGetRequestWithResources(): void
    {
        $client = $this->createClient('http://example.com');

        $request = $client->createGetRequest(new Uri('/myResources'));

        self::assertEquals(
            'http://example.com/myResources',
            $request->uri()->__toString()
        );
    }

    /**
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function testCreateGetRequestWithFilteredResourcesAndInclude(): void
    {
        $client = $this->createClient('http://example.com');

        $request = $client->createGetRequest(new Uri('/myResources?include=test'));
        $request->addFilter('name', 'test');
        $request->requestInclude('myRelationship');

        self::assertEquals(
            'http://example.com/myResources?sort=&filter%5Bname%5D=test&include=test%2CmyRelationship',
            $request->uri()->__toString()
        );
    }

    /**
     * @param string $baseUri
     * @return JsonApiClient
     */
    protected function createClient(string $baseUri): JsonApiClient
    {
        /** @noinspection PhpParamsInspection */
        return new JsonApiClient(
            $baseUri,
            $this->createMock(HttpClientInterface::class),
            $this->createMock(DocumentSerializerInterface::class),
            $this->createMock(DocumentDeserializerInterface::class)
        );
    }
}
