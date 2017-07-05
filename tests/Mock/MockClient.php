<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests\Mock;

use Enm\JsonApi\Client\HttpClient\HttpClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class MockClient implements HttpClientInterface
{
    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $content;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @param array $content
     * @param int $status
     */
    public function __construct(array $content = null, int $status = 200)
    {
        $this->status = $status;
        if (is_array($content)) {
            $this->content = json_encode($content);
        }
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function get(UriInterface $uri, array $headers = []): ResponseInterface
    {
        $this->uri = $uri;
        return $this->createResponse();
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function post(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        $this->uri = $uri;
        return $this->createResponse();
    }

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function patch(UriInterface $uri, string $content, array $headers = []): ResponseInterface
    {
        $this->uri = $uri;
        return $this->createResponse();
    }

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function delete(UriInterface $uri, array $headers = []): ResponseInterface
    {
        $this->uri = $uri;
        return $this->createResponse();
    }

    /**
     * @return Response
     */
    protected function createResponse(): Response
    {
        return new Response($this->status, ['Content-Type' => 'application/vnd.api+json'], $this->content);
    }
}
