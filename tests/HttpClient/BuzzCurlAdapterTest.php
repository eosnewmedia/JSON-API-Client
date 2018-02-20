<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests\HttpClient;

use Buzz\Client\Curl;
use Enm\JsonApi\Client\HttpClient\BuzzCurlAdapter;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class BuzzCurlAdapterTest extends TestCase
{
    public function testAdapter()
    {
        /** @var ClientInterface $client */
        $client = $this->createConfiguredMock(
            Curl::class,
            [
                'sendRequest' => $this->createMock(ResponseInterface::class)
            ]
        );
        /** @var UriInterface $uri */
        $uri = $this->createMock(UriInterface::class);

        $adapter = new BuzzCurlAdapter($client);
        self::assertInstanceOf(ResponseInterface::class, $adapter->get($uri));
        self::assertInstanceOf(ResponseInterface::class, $adapter->post($uri, '{}'));
        self::assertInstanceOf(ResponseInterface::class, $adapter->patch($uri, '{}'));
        self::assertInstanceOf(ResponseInterface::class, $adapter->delete($uri));
    }
}
