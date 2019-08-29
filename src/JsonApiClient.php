<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Exception\HttpException;
use Enm\JsonApi\Exception\JsonApiException;
use Enm\JsonApi\Model\Document\DocumentInterface;
use Enm\JsonApi\Model\Request\Request;
use Enm\JsonApi\Model\Request\RequestInterface;
use Enm\JsonApi\Model\Response\ResponseInterface;
use Enm\JsonApi\Serializer\DocumentDeserializerInterface;
use Enm\JsonApi\Serializer\DocumentSerializerInterface;
use Psr\Http\Message\UriInterface;

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
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var DocumentSerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentDeserializerInterface
     */
    private $deserializer;

    /**
     * @param string $baseUri
     * @param HttpClientInterface $httpClient
     * @param DocumentSerializerInterface $serializer
     * @param DocumentDeserializerInterface $deserializer
     */
    public function __construct(
        string $baseUri,
        HttpClientInterface $httpClient,
        DocumentSerializerInterface $serializer,
        DocumentDeserializerInterface $deserializer
    ) {
        $this->baseUrl = $baseUri;
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
        $this->deserializer = $deserializer;
    }

    /**
     * @param UriInterface $path
     * @return RequestInterface
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function createGetRequest(UriInterface $path): RequestInterface
    {
        return $this->createRequest('GET', $path);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface $body
     * @return RequestInterface
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function createPostRequest(UriInterface $path, DocumentInterface $body): RequestInterface
    {
        return $this->createRequest('POST', $path, $body);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface $body
     * @return RequestInterface
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function createPatchRequest(UriInterface $path, DocumentInterface $body): RequestInterface
    {
        return $this->createRequest('PATCH', $path, $body);
    }

    /**
     * @param UriInterface $path
     * @param DocumentInterface|null $body
     * @return RequestInterface
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    public function createDeleteRequest(UriInterface $path, ?DocumentInterface $body = null): RequestInterface
    {
        return $this->createRequest('DELETE', $path, $body);
    }

    /**
     * @param RequestInterface $request
     * @param bool $exceptionOnFatalError
     * @return ResponseInterface
     * @throws JsonApiException
     */
    public function execute(RequestInterface $request, bool $exceptionOnFatalError = true): ResponseInterface
    {
        $response = $this->httpClient->execute($request, $this);

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
     * @param DocumentInterface|null $requestBody
     * @return string|null
     */
    public function createRequestBody(?DocumentInterface $requestBody): ?string
    {
        return $requestBody ? json_encode($this->serializer->serializeDocument($requestBody)) : null;
    }

    /**
     * @param string|null $responseBody
     * @return DocumentInterface|null
     */
    public function createResponseBody(?string $responseBody): ?DocumentInterface
    {
        $responseBody = (string)$responseBody !== '' ? json_decode($responseBody, true) : null;

        return $responseBody ? $this->deserializer->deserializeDocument($responseBody) : $responseBody;
    }

    /**
     * @param string $method
     * @param UriInterface $path
     * @param DocumentInterface|null $body
     * @return RequestInterface
     * @throws \Enm\JsonApi\Exception\BadRequestException
     */
    protected function createRequest(
        string $method,
        UriInterface $path,
        ?DocumentInterface $body = null
    ): RequestInterface {
        $url = (array)parse_url($this->baseUrl);

        $requestUri = $path;
        if (array_key_exists('scheme', $url)) {
            $requestUri = $requestUri->withScheme((string)$url['scheme']);
        }
        if (array_key_exists('host', $url)) {
            $requestUri = $requestUri->withHost((string)$url['host']);
        }
        if (array_key_exists('port', $url)) {
            $requestUri = $requestUri->withPort((string)$url['port']);
        }
        if (array_key_exists('user', $url)) {
            $password = null;
            if (array_key_exists('pass', $url)) {
                $password = (string)$url['pass'];
            }
            $requestUri = $requestUri->withUserInfo((string)$url['user'], $password);
        }

        $prefix = null;
        if (array_key_exists('path', $url)) {
            $prefix = trim($url['path'], '/');
            if (!empty($prefix)) {
                $prefix .= '/';
            }
        }

        $requestUri = $requestUri->withPath('/' . $prefix . trim($requestUri->getPath(), '/'));

        return new Request($method, $requestUri, $body, $prefix);
    }
}
