<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
interface HttpClientInterface
{
    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function get(UriInterface $uri, array $headers = []): ResponseInterface;

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function post(UriInterface $uri, string $content, array $headers = []): ResponseInterface;

    /**
     * @param UriInterface $uri
     * @param string $content
     * @param array $headers
     * @return ResponseInterface
     */
    public function patch(UriInterface $uri, string $content, array $headers = []): ResponseInterface;

    /**
     * @param UriInterface $uri
     * @param array $headers
     * @return ResponseInterface
     */
    public function delete(UriInterface $uri, array $headers = []): ResponseInterface;
}
