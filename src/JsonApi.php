<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client;

use Enm\JsonApi\AbstractJsonApi;
use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use Enm\JsonApi\Client\Model\Request\FetchInterface;
use Enm\JsonApi\Client\Model\Request\Uri;
use Enm\JsonApi\Exception\HttpException;
use Enm\JsonApi\Exception\JsonApiException;
use Enm\JsonApi\Exception\UnsupportedMediaTypeException;
use Enm\JsonApi\Model\Document\DocumentInterface;
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
class JsonApi extends AbstractJsonApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var array
     */
    private $headers = [
        'Content-Type' => self::CONTENT_TYPE,
        'Accept' => self::CONTENT_TYPE
    ];

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
     * @param FetchInterface $request
     * @param string $id
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchResource(FetchInterface $request, string $id): DocumentInterface
    {
        $uri = $this->buildUri('/' . $request->getType() . '/' . $id, $this->buildQuery($request));

        return $this->handleResponse(
            $this->getHttpClient()->get($uri, $request->getHeaders())
        );
    }

    /**
     * @param FetchInterface $request
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchResources(FetchInterface $request): DocumentInterface
    {
        $uri = $this->buildUri('/' . $request->getType(), $this->buildQuery($request));

        return $this->handleResponse(
            $this->getHttpClient()->get($uri, $request->getHeaders())
        );
    }

    /**
     * @param DocumentInterface $document
     * @param array $headers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function createResource(DocumentInterface $document, array $headers = []): DocumentInterface
    {
        $uri = $this->buildUri(
            '/' . $document->data()->first()->type()
        );

        return $this->handleResponse(
            $this->getHttpClient()->post($uri, $this->buildRequestContent($document), $headers)
        );
    }

    /**
     * @param DocumentInterface $document
     * @param array $headers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function patchResource(DocumentInterface $document, array $headers = []): DocumentInterface
    {
        $uri = $this->buildUri(
            '/' . $document->data()->first()->type() . '/' . $document->data()->first()->id()
        );

        return $this->handleResponse(
            $this->getHttpClient()->patch($uri, $this->buildRequestContent($document), $headers)
        );
    }

    /**
     * @param string $type
     * @param string $id
     * @param array $headers
     * @return DocumentInterface
     * @throws \Exception
     */
    public function deleteResource(string $type, string $id, array $headers = []): DocumentInterface
    {
        $uri = $this->buildUri('/' . $type . '/' . $id, $headers);

        return $this->handleResponse(
            $this->getHttpClient()->delete($uri, $headers)
        );
    }

    /**
     * @param FetchInterface $request
     * @param string $id
     * @param string $relationship
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchRelationship(FetchInterface $request, string $id, string $relationship): DocumentInterface
    {
        $uri = $this->buildUri(
            '/' . $request->getType() . '/' . $id . '/relationships/' . $relationship,
            $this->buildQuery($request)
        );

        return $this->handleResponse(
            $this->getHttpClient()->get($uri, $request->getHeaders())
        );
    }

    /**
     * @param FetchInterface $request
     * @param string $id
     * @param string $relationship
     * @return DocumentInterface
     * @throws \Exception
     */
    public function fetchRelatedResources(FetchInterface $request, string $id, string $relationship): DocumentInterface
    {
        $uri = $this->buildUri(
            '/' . $request->getType() . '/' . $id . '/' . $relationship,
            $this->buildQuery($request)
        );

        return $this->handleResponse(
            $this->getHttpClient()->get($uri, $request->getHeaders())
        );
    }

    /**
     * @param LinkInterface $link
     * @return DocumentInterface
     * @throws \Exception
     */
    public function follow(LinkInterface $link, array $headers = []): DocumentInterface
    {
        $uri = $this->buildUri($link->href());

        return $this->handleResponse(
            $this->getHttpClient()->get($uri, $headers)
        );
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
     * @return HttpClientInterface
     */
    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }


    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
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
     * @param FetchInterface $fetch
     * @return array
     */
    protected function buildQuery(FetchInterface $fetch): array
    {
        $query = [];

        if (count($fetch->getRelationships()) > 0) {
            $query['include'] = implode(',', $fetch->getRelationships());
        }

        foreach ($fetch->getFields() as $type => $typeFields) {
            $query['fields'][$type] = implode(',', $typeFields);
        }

        if (!$fetch->filter()->isEmpty()) {
            $query['filter'] = $fetch->filter()->all();
        }

        if (!$fetch->pagination()->isEmpty()) {
            $query['page'] = $fetch->pagination()->all();
        }

        if (count($fetch->getSorting()) > 0) {
            $query['sort'] = implode(',', $fetch->getSorting());
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
            $this->getLogger()->error(
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
            $this->getLogger()->critical(
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
