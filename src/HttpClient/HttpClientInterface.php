<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\HttpClient;

use Enm\JsonApi\Client\JsonApiClient;
use Enm\JsonApi\Model\Request\RequestInterface;
use Enm\JsonApi\Model\Response\ResponseInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
interface HttpClientInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonApiClient $handler
     * @return ResponseInterface
     */
    public function execute(RequestInterface $request, JsonApiClient $handler): ResponseInterface;
}
