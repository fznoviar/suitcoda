<?php

namespace Suitcoda\Supports;

use Goutte\Client;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerUrl
{
    protected $baseUrl;

    protected $siteLink;

    protected $siteJs;

    protected $siteCss;

    protected $linkToCrawl;

    protected $countBrokenLink;

    protected $client;

    protected $contentType;

    public function __construct(Client $client)
    {
        $this->siteLink = array();
        $this->siteJs = array();
        $this->siteCss = array();
        $this->linkToCrawl = array();
        $this->countBrokenLink = 0;
        $this->contentType = true;
        
        $guzzle = new Guzzle([
            'on_headers' => function (\Psr\Http\Message\ResponseInterface $response) {
                if (($response->getStatusCode() < 300 || $response->getStatusCode() >= 400) &&
                    strpos($response->getHeaderLine('Content-Type'), 'text/html') === false) {
                    $this->contentType = false;
                    throw new \Exception;
                } else {
                    $this->contentType = true;
                }
            }
        ]);
        $client->setClient($guzzle);
        $this->client = $client;
    }

    public function checkIfCrawlable($uri)
    {
        if (empty($uri)) {
            return false;
        }

        $stop_links = array(
            '@^javascript\:void\(0\)$@',
            '@^#.*@',
        );

        foreach ($stop_links as $ptrn) {
            if (preg_match($ptrn, $uri)) {
                return false;
            }
        }

        return true;
    }

    public function normalizeLink($uri)
    {
        $uri = preg_replace('@#.*$@', '', $uri);
        $parse = parse_url($uri);
        if (!empty($uri)) {
            if (!empty($parse['host'])) {
                if (!empty($parse['path'])) {
                    if ($parse['path'] === '/') {
                        return $parse['scheme'] . '://' . $parse['host'];
                    }
                }
                return $uri;
            } elseif (empty($parse['host'])) {
                if (!empty($parse['path'])) {
                    if ($parse['path'][0] !== '/') {
                        return $this->baseUrl . '/' . $parse['path'];
                    }
                    return $this->baseUrl . $parse['path'];
                }
            }
        }
        return $uri;
    }

    public function checkIfExternal($url)
    {
        $baseUrlTrimmed = str_replace(array('http://', 'https://'), '', $this->baseUrl);
        if (preg_match("@http(s)?\://$baseUrlTrimmed@", $url)) {
            return false;
        } else {
            return true;
        }
    }

    public function getSiteLink()
    {
        return $this->siteLink;
    }

    public function getSiteCss()
    {
        return $this->siteCss;
    }

    public function getSiteJs()
    {
        return $this->siteJs;
    }

    public function getCountBrokenLink()
    {
        return $this->countBrokenLink;
    }

    public function setBaseUrl($baseUrl)
    {
        if (strpos($baseUrl, 'http') === false) {
            $this->baseUrl = 'http://' . $baseUrl;
        } else {
            $this->baseUrl = $baseUrl;
        }
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function crawling()
    {
        $count = 0;
        $baseUrl = $this->getBaseUrl();
        $baseUrl = $this->normalizeLink($baseUrl);
        $crawler = $this->client->request('GET', $baseUrl);
        $this->setBaseUrl($this->client->getRequest()->getUri());
        array_push($this->linkToCrawl, $this->baseUrl);

        while (!empty($this->linkToCrawl)) {
            $uri = $this->linkToCrawl[$count];
            unset($this->linkToCrawl[$count]);

            // if ($count === 6) {
            //     dd($this->linkToCrawl);
            //     return;
            // }

            $this->getAllUrl($uri);
            $count += 1;
        }
    }

    protected function getAllUrl($uri)
    {
        $crawler = $this->client->request('GET', $uri);
        $statusCode = $this->client->getResponse()->getStatus();
        $crawlersUrl = $crawler->filter('a')->extract('href');
        foreach ($crawlersUrl as $nodeUrl) {
            if ($this->checkIfCrawlable($nodeUrl)) {
                $nodeUrl = $this->normalizeLink($nodeUrl);
                $inArray = (!in_array($nodeUrl, $this->linkToCrawl) && !in_array($nodeUrl, $this->siteLink));
                if ($this->contentType && $statusCode === 200 && $inArray &&
                    !$this->checkIfExternal($nodeUrl)) {
                    array_push($this->linkToCrawl, $nodeUrl);
                } elseif ($statusCode === 404) {
                    $this->countBrokenLink += 1;
                }
            }

        }
        $crawlersCss = $crawler->filter('link')->extract('href');
        foreach ($crawlersCss as $nodeUrl) {
            $nodeUrl = $this->normalizeLink($nodeUrl);
            $path = pathinfo($nodeUrl, PATHINFO_EXTENSION);
            if (!in_array($nodeUrl, $this->siteCss) && strpos($path, 'css') !== false &&
                !$this->checkIfExternal($nodeUrl)) {
                array_push($this->siteCss, $nodeUrl);
            }
        }

        $crawlersJs = $crawler->filter('script')->extract('src');
        foreach ($crawlersJs as $nodeUrl) {
            $nodeUrl = $this->normalizeLink($nodeUrl);
            $path = pathinfo($nodeUrl, PATHINFO_EXTENSION);
            if (!in_array($nodeUrl, $this->siteJs) && strpos($path, 'js') !== false &&
                !$this->checkIfExternal($nodeUrl)) {
                array_push($this->siteJs, $nodeUrl);
            }
        }

        if (!in_array($uri, $this->siteLink) && $statusCode === 200 && $this->contentType) {
        // dd($this->contentType);
            array_push($this->siteLink, $uri);
        }
    }
}
