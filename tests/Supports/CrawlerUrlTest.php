<?php

namespace SuitTests\Supports;

use Mockery;
use SuitTests\TestCase;
use Suitcoda\Supports\CrawlerUrl;
use Goutte\Client;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CrawlerUrlTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testNormalizeLink()
    {
        $uri = 'http://foobar.com';
        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setClient')->andReturn($guzzle);

        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl($uri);

        $this->assertEquals($uri, $crawl->normalizeLink('http://foobar.com/'));
        $this->assertEquals($uri . '/en', $crawl->normalizeLink('en'));
        $this->assertEquals($uri . '/en', $crawl->normalizeLink($uri . '/en'));
        $this->assertEquals($uri . '/en/test', $crawl->normalizeLink($uri . '/en/test/'));
        $this->assertEquals('http://baz.com', $crawl->normalizeLink('http://baz.com'));
        $this->assertEquals('http://baz.com/test', $crawl->normalizeLink('http://baz.com/test'));
        $this->assertEquals('http://baz.com/test', $crawl->normalizeLink('http://baz.com/test/'));
        $this->assertEquals('http://baz.com', $crawl->normalizeLink('//baz.com'));
    }

    public function testUrlCrawlable()
    {
        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setClient')->andReturn($guzzle);
        

        $crawl = new CrawlerUrl($client);

        $this->assertEquals(true, $crawl->checkIfCrawlable('foobar.com'));
    }

    public function testUrlNotCrawlable()
    {
        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setClient')->andReturn($guzzle);
        
        $crawl = new CrawlerUrl($client);

        $this->assertEquals(false, $crawl->checkIfCrawlable('#'));
        $this->assertEquals(false, $crawl->checkIfCrawlable(''));
    }

    public function testUrlIsExternalUrl()
    {
        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setClient')->andReturn($guzzle);

        $crawl = new CrawlerUrl($client);

        $crawl->setBaseUrl('http://foobar.com');
        $this->assertEquals(true, $crawl->checkIfExternal('http://test.com'));
    }

    public function testUrlIsNotExternalUrl()
    {
        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setClient')->andReturn($guzzle);
        
        $crawl = new CrawlerUrl($client);

        $crawl->setBaseUrl('foobar.com');
        $this->assertEquals(false, $crawl->checkIfExternal('http://foobar.com/test'));
    }

    public function testDoRequestHtml()
    {
        $client = $this->getMockClient()->makePartial();
        $response = $this->getMockStatusCodeSuccess()->makePartial();

        $client->shouldReceive('getResponse')->andReturn($response);

        $crawling = new CrawlerUrl($client);
        $crawling->doRequest('http://foobar.com');

        $this->assertEquals(['http://foobar.com'], $crawling->getSiteUrl());
    }

    public function testDoRequestBrokenLink()
    {
        $client = $this->getMockClient()->makePartial();
        $response = $this->getMockStatusCodeFailed()->makePartial();

        $client->shouldReceive('getResponse')->andReturn($response);

        $crawling = new CrawlerUrl($client);
        $result = $crawling->doRequest('http://foobar.com/test');

        $this->assertEquals(null, $result);
        $this->assertEquals(['http://foobar.com/test'], $crawling->getSiteBrokenLink());
    }

    public function testDoRequestFile()
    {
        $client = $this->getMockClient()->makePartial();
        $response = $this->getMockStatusCodeFailed()->makePartial();

        $client->shouldReceive('getResponse')->andReturn($response);

        $crawling = new CrawlerUrl($client);
        $crawling->setContentType(false);
        $result = $crawling->doRequest('http://foobar.com/test.pdf');

        $this->assertEquals(null, $result);
        $this->assertEquals(['http://foobar.com/test.pdf'], $crawling->getSiteFile());
    }

    public function testCheckNotInList()
    {
        $client = $this->getMockClient()->makePartial();

        $crawling = new CrawlerUrl($client);
        $result = $crawling->checkNotInList('http://foobar.com');

        $this->assertEquals(true, $result);
    }

    public function testDoRequestCatchException()
    {
        $client = $this->getMockClientException();

        $crawling = new CrawlerUrl($client);
        $result = $crawling->doRequest('http://foobar.com/unknown');

        $this->assertEquals(null, $result);
    }

    public function testCrawler()
    {
        $client = $this->getMockClient();
        $response = $this->getMockStatusCodeSuccess()->makePartial();

        $client->shouldReceive('getResponse')->andReturn($response);

        $crawling = new CrawlerUrl($client);
        $crawling->setBaseUrl('http://foobar.com');
        $crawling->start();

        $this->assertEquals([
            'http://foobar.com',
            'http://foobar.com/test',
            'http://foobar.com/baz'
        ], $crawling->getSiteUrl());
    }

    public function testDumpGet()
    {
        $client = $this->getMockClient();

        $crawling = new CrawlerUrl($client);

        $this->assertEquals([], $crawling->getSiteCss());
        $this->assertEquals([], $crawling->getSiteJs());
        $this->assertEquals([], $crawling->getSiteFile());
        $this->assertEquals([], $crawling->getSiteBrokenLink());
    }

    protected function getMockClient()
    {
        $guzzle = $this->getMockGuzzle()->makePartial();
        $domCrawler = $this->getMockDomCrawler()->makePartial();
        $client = Mockery::mock(Client::class);

        $client->shouldReceive('setClient')->andReturn($guzzle);
        $client->shouldReceive('request')->andReturn($domCrawler);

        return $client;
    }

    protected function getMockClientException()
    {
        $guzzle = $this->getMockGuzzle()->makePartial();
        $client = Mockery::mock(Client::class);

        $client->shouldReceive('setClient')->andReturn($guzzle);
        $client->shouldReceive('request')->times(4)->andThrow(new \RuntimeException);

        return $client;
    }

    protected function getMockGuzzle()
    {
        $guzzle = Mockery::mock(Guzzle::class);

        return $guzzle;
    }

    protected function getMockStatusCodeSuccess()
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getStatus')->andReturn(200);

        return $response;
    }

    protected function getMockStatusCodeFailed()
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getStatus')->andReturn(404);

        return $response;
    }

    protected function getMockDomCrawler()
    {
        $links = [
            'http://foobar.com',
            'http://foobar.com/test',
            'http://foobar.com/baz'
        ];

        $domCrawler = Mockery::mock(Crawler::class);
        $domCrawler->shouldReceive('filter')->andReturn($domCrawler);
        $domCrawler->shouldReceive('filterXPath')->andReturn($domCrawler);
        $domCrawler->shouldReceive('extract')->andReturn($links);

        return $domCrawler;
    }
}
