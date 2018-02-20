<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\HttpClient;

use Buzz\Client\Curl;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function get(UriInterface $uri, array $headers = []): ResponseInterface
    {
        return $this->client->sendRequest(new Request('GET', $uri, $headers));
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function post(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        return $this->client->sendRequest(new Request('POST', $uri, $headers, $content));
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function patch(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        return $this->client->sendRequest(new Request('PATCH', $uri, $headers, $content));
    }

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function delete(UriInterface $uri, array $headers = []): ResponseInterface
    {
        return $this->client->sendRequest(new Request('DELETE', $uri, $headers));
    }
}
