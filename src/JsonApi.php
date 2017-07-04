<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Client\Model\Request\Uri;
use Enm\JsonApi\Exception\BadRequestException;
use Enm\JsonApi\Exception\HttpException;
use Enm\JsonApi\Exception\JsonApiException;
use Enm\JsonApi\Exception\UnsupportedMediaTypeException;
use Enm\JsonApi\JsonApiInterface;
use Enm\JsonApi\JsonApiTrait;
use Enm\JsonApi\Model\Document\DocumentInterface;
use Enm\JsonApi\Model\Request\JsonApiRequestInterface;
use Enm\JsonApi\Model\Request\ResourceRequestInterface;
use Enm\JsonApi\Model\Request\SaveRequestInterface;
use Enm\JsonApi\Model\Resource\Link\LinkInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class JsonApi implements LoggerAwareInterface, JsonApiInterface
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
     * @param ResourceRequestInterface $request
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetch(ResourceRequestInterface $request): DocumentInterface
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
        $uri = $this->buildUri($this->buildPath($request));

        return $this->handleResponse(
            $this->httpClient()->delete($uri, $request->headers()->all())
        );
    }

    /**
     * @param string $relationship
     * @param ResourceRequestInterface $request
     * @param bool $onlyIdentifiers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchRelationship(
        string $relationship,
        ResourceRequestInterface $request,
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
        $uri = new Uri(rtrim($this->baseUrl, '/') . $path);
        if ($uri->getQuery() !== '') {
            $urlQuery = [];
            parse_str($uri->getQuery(), $urlQuery);
            $uri = $uri->withQuery(http_build_query(array_merge($urlQuery, $query)));
        }

        return $uri;
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
     * @param ResourceRequestInterface $request
     * @return array
     */
    protected function buildQuery(ResourceRequestInterface $request): array
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
                $sorting[] = ($direction === ResourceRequestInterface::ORDER_DESC ? '-' : '') . $field;
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
        if (count($contentTypeHeader) === 0 || !strpos($contentTypeHeader, self::CONTENT_TYPE)) {
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

        return $this->deserializeDocument($documentData);
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
