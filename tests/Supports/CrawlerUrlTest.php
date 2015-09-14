<?php

namespace SuitTests\Supports;

use Mockery;
use SuitTests\TestCase;
use Suitcoda\Supports\CrawlerUrl;
use Goutte\Client;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerUrlTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testUrlCrawlable()
    {
        $client = Mockery::mock(Client::class);
        $crawl = new CrawlerUrl($client);

        $this->assertEquals(true, $crawl->checkIfCrawlable('foobar.com'));
    }

    public function testUrlNotCrawlable()
    {
        $client = Mockery::mock(Client::class);
        $crawl = new CrawlerUrl($client);

        $this->assertEquals(false, $crawl->checkIfCrawlable('#'));
        $this->assertEquals(false, $crawl->checkIfCrawlable(''));
        $this->assertEquals(1, $crawl->getCountBrokenLink());

    }

    public function testUrlIsExternalUrl()
    {
        $client = Mockery::mock(Client::class);
        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('http://foobar.com');

        $this->assertEquals(true, $crawl->checkIfExternal('http://test.com'));
    }

    public function testUrlIsNotExternalUrl()
    {
        $client = Mockery::mock(Client::class);
        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('foobar.com');

        $this->assertEquals(false, $crawl->checkIfExternal('http://foobar.com/test'));
    }

    public function testCrawling()
    {
        $links = [
            'https://suitmedia.com/index.php/client',
            'https://suitmedia.com/index.php/home'
        ];

        $client = Mockery::mock(Client::class);
        $response = Mockery::mock(Response::class);
        $domCrawler = Mockery::mock(Crawler::class);
        // dd($domCrawler);

        $client->shouldReceive('request')->andReturn($domCrawler);
        $client->shouldReceive('getResponse')->andReturn($response);
        $response->shouldReceive('getStatus')->andReturn(200);
        $response->shouldReceive('getHeader')->with('Content-Type')->andReturn('text/html');
        $domCrawler->shouldReceive('filter')->with('a')->andReturn($domCrawler);
        $domCrawler->shouldReceive('extract')->andReturn($links);

        $crawl = new CrawlerUrl($client);
        $crawl->setBaseUrl('suitmedia.com');
        
        $crawl->crawling();
        $this->assertEquals($links, $crawl->getSiteLink());
    }
}
