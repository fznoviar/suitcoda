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

        $this->assertEquals($uri, $crawl->normalizeLink($uri . '/'));
        $this->assertEquals($uri . '/en', $crawl->normalizeLink('en'));
        $this->assertEquals($uri . '/en', $crawl->normalizeLink($uri . '/en'));
        $this->assertEquals('http://baz.com', $crawl->normalizeLink('http://baz.com'));
        $this->assertEquals('http://baz.com/test', $crawl->normalizeLink('http://baz.com/test'));
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
        // $this->assertEquals(1, $crawl->getCountBrokenLink());
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

    public function testCrawlingUrl()
    {
        $links = [
        'http://foobar.com',
            'http://foobar.com/test',
            'http://foobar.com/baz'
        ];

        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $domCrawler = Mockery::mock(Crawler::class);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $client->shouldReceive('request')->andReturn($domCrawler);
        $client->shouldReceive('getRequest')->andReturn($request);
        $client->shouldReceive('setClient')->andReturn($guzzle);
        $client->shouldReceive('getResponse')->andReturn($response);
        $domCrawler->shouldReceive('filter')->andReturn($domCrawler);
        $domCrawler->shouldReceive('extract')->andReturn([
            'http://foobar.com/test',
            '/baz'
        ]);
        $request->shouldReceive('getUri')->andReturn('http://foobar.com');
        $response->shouldReceive('getStatus')->andReturn(200);

        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('http://foobar.com');
        
        $crawl->crawling();
        $this->assertEquals($links, $crawl->getSiteLink());
    }

    public function testCrawlingCss()
    {
        $css = [
            'http://foobar.com/assets/css/main.css',
        ];

        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $domCrawler = Mockery::mock(Crawler::class);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $client->shouldReceive('setClient')->andReturn($guzzle);
        $client->shouldReceive('request')->andReturn($domCrawler);
        $client->shouldReceive('getRequest')->andReturn($request);
        $client->shouldReceive('getResponse')->andReturn($response);
        $domCrawler->shouldReceive('filter')->andReturn($domCrawler);
        $domCrawler->shouldReceive('extract')->andReturn($css);
        $request->shouldReceive('getUri');
        $response->shouldReceive('getStatus')->andReturn(200);

        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('foobar.com');
        
        $crawl->crawling();
        $this->assertEquals($css, $crawl->getSiteCss());
    }

    public function testCrawlingJs()
    {
        $js = [
            'http://foobar.com/assets/js/main.js',
        ];

        $guzzle = Mockery::mock(Guzzle::class);
        $client = Mockery::mock(Client::class);
        $domCrawler = Mockery::mock(Crawler::class);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $client->shouldReceive('setClient')->andReturn($guzzle);
        $client->shouldReceive('request')->andReturn($domCrawler);
        $client->shouldReceive('getRequest')->andReturn($request);
        $client->shouldReceive('getResponse')->andReturn($response);
        $domCrawler->shouldReceive('filter')->andReturn($domCrawler);
        $domCrawler->shouldReceive('extract')->andReturn($js);
        $request->shouldReceive('getUri');
        $response->shouldReceive('getStatus')->andReturn(200);

        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('foobar.com');
        
        $crawl->crawling();
        $this->assertEquals($js, $crawl->getSitejs());
    }
}
