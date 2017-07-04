<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Tests\Model\Request;

use Enm\JsonApi\Client\Model\Request\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
class UriTest extends TestCase
{
    public function testUri()
    {
        $uriString = 'http://user:pass@example.com:80/test?test=2#abc';

        self::assertEquals($uriString, (string)new Uri($uriString));

        $uriString = 'https://example.com:80/test?test=2#abc';
        self::assertEquals($uriString, (string)new Uri($uriString));

        $uriString = 'https://example.com:80/test';
        self::assertEquals($uriString, (string)new Uri($uriString));

        $uriString = 'https://example.com/test';
        self::assertEquals($uriString, (string)new Uri($uriString));

        $uriString = 'http://example.com';
        self::assertEquals($uriString, (string)new Uri($uriString));
    }

    public function testWithScheme()
    {
        self::assertEquals(
            'https://example.com',
            (string)(new Uri('http://example.com'))->withScheme('https')
        );
    }

    public function testWithHost()
    {
        self::assertEquals(
            'http://test.org',
            (string)(new Uri('http://example.com'))->withHost('test.org')
        );
    }


    public function testWithUser()
    {
        self::assertEquals(
            'http://eos@example.com',
            (string)(new Uri('http://example.com'))->withUserInfo('eos')
        );
        self::assertEquals(
            'http://eos:pass@example.com',
            (string)(new Uri('http://example.com'))->withUserInfo('eos', 'pass')
        );
    }

    public function testWithPort()
    {
        self::assertEquals(
            'http://example.com:8000',
            (string)(new Uri('http://example.com'))->withPort(8000)
        );
    }

    public function testWithPath()
    {
        self::assertEquals(
            'http://example.com/test',
            (string)(new Uri('http://example.com'))->withPath('test')
        );
        self::assertEquals(
            'http://example.com/test',
            (string)(new Uri('http://example.com'))->withPath('/test')
        );
    }

    public function testWithQuery()
    {
        self::assertEquals(
            'http://example.com?hallo=welt',
            (string)(new Uri('http://example.com?hallo2=welt'))->withQuery('hallo=welt')
        );
        self::assertEquals(
            'http://example.com?hallo=welt',
            (string)(new Uri('http://example.com'))->withQuery('hallo=welt')
        );
    }

    public function testWithFragment()
    {
        self::assertEquals(
            'http://example.com#halloWelt',
            (string)(new Uri('http://example.com'))->withFragment('halloWelt')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidUri()
    {
        new Uri('hallo,welt');
    }
}
