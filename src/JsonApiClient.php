<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client;

use Enm\JsonApi\Client\HttpClient\Response\HttpResponse;
use Enm\JsonApi\Exception\HttpException;
use Enm\JsonApi\Model\Document\DocumentInterface;
use Enm\JsonApi\Model\Request\Request;
use Enm\JsonApi\Model\Request\RequestInterface;
use Enm\JsonApi\Model\Response\ResponseInterface;
use Enm\JsonApi\Serializer\DocumentDeserializerInterface;
use Enm\JsonApi\Serializer\DocumentSerializerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class JsonApiClient
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var DocumentSerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentDeserializerInterface
     */
    private $deserializer;

    /**
     * @param string $baseUrl
     * @param ClientInterface $httpClient
     * @param UriFactoryInterface $uriFactory
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param DocumentSerializerInterface $serializer
     * @param DocumentDeserializerInterface $deserializer
     */
    public function __construct(
        string $baseUrl,
        ClientInterface $httpClient,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        DocumentSerializerInterface $serializer,
        DocumentDeserializerInterface $deserializer
    ) {
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient;
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->serializer = $serializer;
        $this->deserializer = $deserializer;
    }

    /**
     * @param UriInterface $path
     * @return RequestInterface
     * @throws Throwable
     */
    public function createGetRequest(UriInterface $path): RequestInterface
    {
        return $this->createJsonApiRequest('GET', $path);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface $body
     * @return RequestInterface
     * @throws Throwable
     */
    public function createPostRequest(UriInterface $path, DocumentInterface $body): RequestInterface
    {
        return $this->createJsonApiRequest('POST', $path, $body);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface $body
     * @return RequestInterface
     * @throws Throwable
     */
    public function createPatchRequest(UriInterface $path, DocumentInterface $body): RequestInterface
    {
        return $this->createJsonApiRequest('PATCH', $path, $body);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface|null $body
     * @return RequestInterface
     * @throws Throwable
     */
    public function createDeleteRequest(UriInterface $path, ?DocumentInterface $body = null): RequestInterface
    {
        return $this->createJsonApiRequest('DELETE', $path, $body);
    }

    /**
     * @param RequestInterface $request
     * @param bool $exceptionOnFatalError
     * @return ResponseInterface
     * @throws Throwable
     */
    public function execute(RequestInterface $request, bool $exceptionOnFatalError = true): ResponseInterface
    {
        $httpRequest = $this->requestFactory->createRequest($request->method(), $request->uri());
        foreach ($request->headers()->all() as $header => $value) {
            $httpRequest = $httpRequest->withHeader($header, $value);
        }

        if ($request->requestBody()) {
            $httpRequest = $httpRequest->withBody(
                $this->streamFactory->createStream(
                    json_encode($this->serializer->serializeDocument($request->requestBody()))
                )
            );
        }

        $httpResponse = $this->httpClient->sendRequest($httpRequest);

        $response = new HttpResponse(
            $httpResponse->getStatusCode(),
            $httpResponse->getHeaders(),
            $this->createResponseBody($httpResponse->getBody()->getContents())
        );

        if ($exceptionOnFatalError && $response->status() >= 400) {
            $message = 'Non successful http status returned (' . $response->status() . ').';

            $document = $response->document();
            if ($document && !$document->errors()->isEmpty()) {
                foreach ($document->errors()->all() as $error) {
                    $message .= '\n' . $error->title();
                }
            }

            throw new HttpException($response->status(), $message);
        }

        return $response;
    }

    /**
     * @param string|null $responseBody
     * @return DocumentInterface|null
     */
    private function createResponseBody(?string $responseBody): ?DocumentInterface
    {
        $responseBody = (string)$responseBody !== '' ? json_decode($responseBody, true) : null;

        return $responseBody ? $this->deserializer->deserializeDocument($responseBody) : $responseBody;
    }

    /**
     * @param string $method
     * @param UriInterface $path
     * @param DocumentInterface|null $body
     * @return RequestInterface
     * @throws Throwable
     */
    private function createJsonApiRequest(
        string $method,
        UriInterface $path,
        ?DocumentInterface $body = null
    ): RequestInterface {
        $uri = $this->uriFactory->createUri($this->baseUrl);

        if ((string)$path->getHost() !== '') {
            $uri = $uri->withScheme($path->getScheme())
                ->withHost($path->getHost())
                ->withPort($path->getPort());

            if ((string)$path->getUserInfo() !== '') {

                $parts = explode(':', $path->getUserInfo(), 2);
                $password = null;
                if (count($parts) === 2) {
                    $password = $parts[1];
                }
                $uri = $uri->withUserInfo($parts[0], $password);
            }
        }

        if ((string)$path->getQuery() !== '') {

            if ((string)$uri->getQuery() === '') {
                $uri = $uri->withQuery($path->getQuery());
            } else {
                $pathQuery = [];
                parse_str($path->getQuery(), $pathQuery);
                $uriQuery = [];
                parse_str($uri->getQuery(), $uriQuery);
                $uri->withQuery(http_build_query(array_merge($uriQuery, $pathQuery)));
            }
        }

        $prefix = null;
        if ((string)$uri->getPath() !== '') {
            $prefix = trim($uri->getPath(), '/');
            if (!empty($prefix)) {
                $prefix .= '/';
            }
        }

        $uri = $uri->withPath('/' . $prefix . trim($path->getPath(), '/'));

        return new Request($method, $uri, $body, $prefix);
    }
}
