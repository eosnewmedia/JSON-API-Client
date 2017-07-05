<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests\HttpClient;

use Enm\JsonApi\Client\HttpClient\GuzzleAdapter;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class GuzzleAdapterTest extends TestCase
{
    public function testAdapter()
    {
        /** @var ClientInterface $guzzle */
        $guzzle = $this->createConfiguredMock(
            ClientInterface::class,
            [
                'send' => $this->createMock(ResponseInterface::class)
            ]
        );
        /** @var UriInterface $uri */
        $uri = $this->createMock(UriInterface::class);

        $client = new GuzzleAdapter($guzzle);
        self::assertInstanceOf(ResponseInterface::class, $client->get($uri));
        self::assertInstanceOf(ResponseInterface::class, $client->post($uri, '{}'));
        self::assertInstanceOf(ResponseInterface::class, $client->patch($uri, '{}'));
        self::assertInstanceOf(ResponseInterface::class, $client->delete($uri));
    }
}
