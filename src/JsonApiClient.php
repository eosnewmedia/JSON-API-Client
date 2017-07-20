<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Model\Request\SaveSingleResourceRequest;
use GuzzleHttp\Psr7\Uri;
use Enm\JsonApi\Exception\BadRequestException;
use Enm\JsonApi\Exception\HttpException;
use Enm\JsonApi\Exception\JsonApiException;
use Enm\JsonApi\Exception\UnsupportedMediaTypeException;
use Enm\JsonApi\JsonApiInterface;
use Enm\JsonApi\JsonApiTrait;
use Enm\JsonApi\Model\Document\DocumentInterface;
use Enm\JsonApi\Model\Request\FetchRequest;
use Enm\JsonApi\Model\Request\JsonApiRequest;
use Enm\JsonApi\Model\Request\JsonApiRequestInterface;
use Enm\JsonApi\Model\Request\FetchRequestInterface;
use Enm\JsonApi\Model\Request\SaveRequestInterface;
use Enm\JsonApi\Model\Resource\Link\LinkInterface;
use Enm\JsonApi\Model\Resource\ResourceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class JsonApiClient implements LoggerAwareInterface, JsonApiInterface
{
    use LoggerAwareTrait;
    use JsonApiTrait;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @param string $baseUrl
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $baseUrl, HttpClientInterface $httpClient)
    {
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient;
    }

    /**
     * Create a simple json api request which is currently used for delete requests
     *
     * @param string $type
     * @param string $id
     * @return JsonApiRequestInterface
     * @throws JsonApiException
     */
    public function createJsonApiRequest(string $type, string $id = ''): JsonApiRequestInterface
    {
        return new JsonApiRequest($type, $id);
    }

    /**
     * Create a fetch request to retrieve one (type and id given) or many (only type given) resources from server
     *
     * @param string $type
     * @param string $id
     * @return FetchRequestInterface
     * @throws JsonApiException
     */
    public function createFetchRequest(string $type, string $id = ''): FetchRequestInterface
    {
        return new FetchRequest($type, $id);
    }

    /**
     * Create a request to create or update a given resource object on server side
     *
     * @param ResourceInterface $resource
     * @param bool $patch
     * @return SaveRequestInterface
     * @throws JsonApiException
     */
    public function createSaveSingleResourceRequest(ResourceInterface $resource, bool $patch = false): SaveRequestInterface
    {
        return new SaveSingleResourceRequest(
            $this->singleResourceDocument($resource),
            $patch ? $resource->id() : ''
        );
    }

    /**
     * @param FetchRequestInterface $request
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetch(FetchRequestInterface $request): DocumentInterface
    {
        $uri = $this->buildUri($this->buildPath($request), $this->buildQuery($request));

        return $this->handleResponse(
            $this->httpClient()->get($uri, $request->headers()->all())
        );
    }

    /**
     * @param SaveRequestInterface $request
     * @return DocumentInterface
     * @throws \Exception
     */
    public function save(SaveRequestInterface $request): DocumentInterface
    {
        $uri = $this->buildUri($this->buildPath($request));

        return $this->handleResponse(
            $this->httpClient()->post(
                $uri,
                $this->buildRequestContent($request->document()),
                $request->headers()->all()
            )
        );
    }

    /**
     * @param JsonApiRequestInterface $request
     * @return DocumentInterface
     * @throws \Exception
     */
    public function delete(JsonApiRequestInterface $request): DocumentInterface
    {
        if (!$request->containsId()) {
            throw new BadRequestException('Request does not contain a resource id!');
        }

        $uri = $this->buildUri($this->buildPath($request));

        return $this->handleResponse(
            $this->httpClient()->delete($uri, $request->headers()->all())
        );
    }

    /**
     * @param string $relationship
     * @param FetchRequestInterface $request
     * @param bool $onlyIdentifiers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchRelationship(
        string $relationship,
        FetchRequestInterface $request,
        bool $onlyIdentifiers = false
    ): DocumentInterface {
        if (!$request->containsId()) {
            throw new BadRequestException('Request does not contain a resource id!');
        }

        $path = $this->buildPath($request) . '/' . ($onlyIdentifiers ? 'relationships/' : '') . $relationship;
        $uri = $this->buildUri($path, $this->buildQuery($request));

        return $this->handleResponse(
            $this->httpClient()->get($uri, $request->headers()->all())
        );
    }

    /**
     * @param LinkInterface $link
     * @param array $headers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function follow(LinkInterface $link, array $headers = []): DocumentInterface
    {
        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = self::CONTENT_TYPE;
        }
        if (!array_key_exists('Accept', $headers)) {
            $headers['Accept'] = self::CONTENT_TYPE;
        }

        return $this->handleResponse(
            $this->httpClient()->get($this->buildUri($link->href()), $headers)
        );
    }

    /**
     * @return HttpClientInterface
     */
    protected function httpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    /**
     * @return LoggerInterface
     */
    protected function logger(): LoggerInterface
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * @param string $path
     * @param array $query
     * @return UriInterface
     * @throws \InvalidArgumentException
     */
    protected function buildUri(string $path, array $query = []): UriInterface
    {
        $uri = new Uri($this->baseUrl);

        $urlQuery = [];
        parse_str($uri->getQuery(), $urlQuery);

        return $uri->withPath(rtrim($uri->getPath(), '/') . '/' . ltrim($path, '/'))
            ->withQuery(http_build_query(array_merge_recursive($urlQuery, $query)));
    }

    /**
     * @param JsonApiRequestInterface $request
     * @return string
     */
    protected function buildPath(JsonApiRequestInterface $request): string
    {
        $path = '/' . $request->type();
        if ($request->containsId()) {
            $path .= '/' . $request->id();
        }
        return $path;
    }


    /**
     * @param DocumentInterface $content
     * @return string
     */
    protected function buildRequestContent(DocumentInterface $content): string
    {
        return json_encode($this->serializeDocument($content));
    }

    /**
     * @param FetchRequestInterface $request
     * @return array
     */
    protected function buildQuery(FetchRequestInterface $request): array
    {
        $query = [];

        if (count($request->includes()) > 0) {
            $query['include'] = implode(',', $request->includes());
        }

        foreach ($request->fields() as $type => $typeFields) {
            $query['fields'][$type] = implode(',', $typeFields);
        }

        if (!$request->filter()->isEmpty()) {
            $query['filter'] = $request->filter()->all();
        }

        if (!$request->pagination()->isEmpty()) {
            $query['page'] = $request->pagination()->all();
        }

        if (!$request->sorting()->isEmpty()) {
            $sorting = [];
            foreach ($request->sorting()->all() as $field => $direction) {
                $sorting[] = ($direction === FetchRequestInterface::ORDER_DESC ? '-' : '') . $field;
            }
            $query['sort'] = implode(',', $sorting);
        }

        return $query;
    }

    /**
     * @param ResponseInterface $response
     * @return DocumentInterface
     * @throws JsonApiException
     */
    protected function handleResponse(ResponseInterface $response): DocumentInterface
    {
        $this->validateContentType($response);

        $document = $this->buildDocument($response);

        $this->logDocumentErrors($document);
        $this->handleCriticalErrors($response, $document);

        return $document;
    }

    /**
     * @param ResponseInterface $response
     * @throws UnsupportedMediaTypeException
     * @return void
     */
    protected function validateContentType(ResponseInterface $response)
    {
        $contentTypeHeader = $response->getHeader('Content-Type');

        if (count($contentTypeHeader) === 0 || strpos($contentTypeHeader[0], self::CONTENT_TYPE) === false) {
            throw new UnsupportedMediaTypeException('Invalid content type: ' . $contentTypeHeader[0]);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return DocumentInterface
     * @throws JsonApiException
     */
    protected function buildDocument(ResponseInterface $response): DocumentInterface
    {
        $body = (string)$response->getBody();
        $documentData = $body !== '' ? json_decode($body, true) : [];

        if (!is_array($documentData)) {
            throw new JsonApiException(json_last_error_msg(), json_last_error());
        }

        $document = $this->deserializeDocument($documentData);
        $document->withHttpStatus($response->getStatusCode());

        return $document;
    }

    /**
     * @param DocumentInterface $document
     * @return void
     */
    protected function logDocumentErrors(DocumentInterface $document)
    {
        foreach ($document->errors()->all() as $error) {
            $this->logger()->error(
                $error->title(),
                [
                    'detail' => $error->detail(),
                    'status' => $error->status(),
                    'code' => $error->code(),
                    'meta' => $error->metaInformation()->all()
                ]
            );
        }
    }

    /**
     * @param ResponseInterface $response
     * @param DocumentInterface $document
     * @throws JsonApiException
     * @return void
     */
    protected function handleCriticalErrors(ResponseInterface $response, DocumentInterface $document)
    {
        if (!in_array($response->getStatusCode(), [200, 202, 204], true)) {
            $this->logger()->critical(
                (string)$response->getReasonPhrase(),
                [
                    'httpStatus' => $response->getStatusCode()
                ]
            );

            $errorMessages = [];
            foreach ($document->errors()->all() as $error) {
                $errorMessages[] = $error->title();
            }

            $message = implode('; ', $errorMessages);
            if ($message === '') {
                $message = (string)$response->getReasonPhrase();
            }

            throw new HttpException($response->getStatusCode(), $message);
        }
    }
}
