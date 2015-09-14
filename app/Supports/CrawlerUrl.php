<?php

namespace Suitcoda\Supports;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerUrl
{
    protected $baseUrl;

    protected $siteLink;

    protected $linkToCrawl;

    protected $countBrokenLink;

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->siteLink = array();
        $this->linkToCrawl = array();
        $this->countBrokenLink = 0;
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
                $this->countBrokenLink += 1;
                return false;
            }
        }

        return true;
    }

    protected function normalizeLink($uri)
    {
        $uri = preg_replace('@#.*$@', '', $uri);

        return $uri;
    }

    public function crawling()
    {
        $count = 0;
        
        $baseUrl = $this->getBaseUrl();
        $crawler = $this->client->request('GET', $baseUrl);
        $statusCode = $this->client->getResponse()->getStatus();
        $contentType = $this->client->getResponse()->getHeader('Content-Type');
        
        if ($statusCode == 200) {
            if (strpos($contentType, 'text/html') !== false) {
                $this->getAllUrl($crawler);
            }
        }

        $this->recursiveCrawl();
    }

    protected function getAllUrl($crawler)
    {
        $crawlers = $crawler->filter('a')->extract('href');
        foreach ($crawlers as $nodeUrl) {
            $nodeUrl = $this->normalizeLink($nodeUrl);
            if ($this->checkIfCrawlable($nodeUrl) && !$this->checkIfExternal($nodeUrl)) {
                $normalUri = $this->normalizeLink($nodeUrl);
                if (!in_array($normalUri, $this->siteLink)) {
                    array_push($this->siteLink, $normalUri);
                }
            }
        }

        return $this->siteLink;
    }

    public function checkIfExternal($url)
    {
        $baseUrlTrimmed = str_replace(array('http://', 'https://'), '', $this->getBaseUrl());
        if (preg_match("@http(s)?\://$baseUrlTrimmed@", $url)) {
            return false;
        } else {
            return true;
        }
    }

    protected function recursiveCrawl()
    {
        if (empty($this->linkToCrawl)) {
            $this->linkToCrawl = $this->siteLink;
        }
        $temp = $this->siteLink;
        foreach ($this->linkToCrawl as $link) {
            $crawler = $this->client->request('GET', $link);
            $statusCode = $this->client->getResponse()->getStatus();
            $contentType = $this->client->getResponse()->getHeader('Content-Type');
            
            if ($statusCode == 200) {
                if (strpos($contentType, 'text/html') !== false) {
                    $this->getAllUrl($crawler);
                }
            }
        }

        $this->linkToCrawl = array_diff($this->siteLink, $temp);
        if (count($this->linkToCrawl) !== 0) {
            $this->recursiveCrawl();
        }
    }

    public function getSiteLink()
    {
        sort($this->siteLink);
        return $this->siteLink;
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
}
