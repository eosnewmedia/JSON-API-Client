<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\HttpClient;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class GuzzleAdapter implements HttpClientInterface
{
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @param ClientInterface $guzzleClient
     */
    public function __construct(ClientInterface $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @return ClientInterface
     */
    protected function guzzleClient(): ClientInterface
    {
        return $this->guzzleClient;
    }

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(UriInterface $uri, array $headers = []): ResponseInterface
    {
        return $this->guzzleClient()->send(
            new Request('GET', $uri, $headers), ['http_errors' => false]
        );
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        return $this->guzzleClient()->send(
            new Request('POST', $uri, $headers, $content), ['http_errors' => false]
        );
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function patch(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        return $this->guzzleClient()->send(
            new Request('PATCH', $uri, $headers, $content), ['http_errors' => false]
        );
    }

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(UriInterface $uri, array $headers = []): ResponseInterface
    {
        return $this->guzzleClient()->send(
            new Request('DELETE', $uri, $headers), ['http_errors' => false]
        );
    }
}
