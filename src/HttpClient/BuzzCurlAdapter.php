<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\HttpClient;

use Buzz\Client\Curl;
use Enm\JsonApi\Client\HttpClient\Response\HttpResponse;
use Enm\JsonApi\Client\JsonApiClient;
use Enm\JsonApi\Model\Request\RequestInterface;
use Enm\JsonApi\Model\Response\ResponseInterface;
use GuzzleHttp\Psr7\Request;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class BuzzCurlAdapter implements HttpClientInterface
{
    /**
     * @var Curl
     */
    private $client;

    /**
     * @param Curl $client
     */
    public function __construct(Curl $client)
    {
        $this->client = $client;
    }

    /**
     * @param RequestInterface $request
     * @param JsonApiClient $handler
     * @return ResponseInterface
     */
    public function execute(RequestInterface $request, JsonApiClient $handler): ResponseInterface
    {
        $response = $this->client->sendRequest(
            new Request(
                $request->method(),
                $request->uri(),
                $request->headers()->all(),
                $handler->createRequestBody($request->requestBody())
            ),
            [
                'http_errors' => false
            ]
        );

        return new HttpResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            $handler->createResponseBody((string)$response->getBody())
        );
    }
}
