<?php

namespace Suitcoda\Supports;

use Goutte\Client;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerUrl
{
    protected $retry = 3;

    protected $baseUrl;

    protected $siteUrl;

    protected $siteJs;

    protected $siteCss;

    protected $siteBrokenLink;

    protected $siteFile;

    protected $client;

    protected $contentType;

    public function __construct(Client $client)
    {
        $this->siteUrl = array();
        $this->siteJs = array();
        $this->siteCss = array();
        $this->linkToCrawl = array();
        $this->siteBrokenLink = array();
        $this->siteFile = array();
        $this->contentType = true;
        
        $guzzle = new Guzzle([
            'on_headers' => function (\Psr\Http\Message\ResponseInterface $response) {
                if (strpos($response->getHeaderLine('Content-Type'), 'text/html') === false) {
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

    /**
     * Check url is crawlable or not
     * @param  string $url
     * @return boolean
     */
    public function checkIfCrawlable($url)
    {
        if (empty($url)) {
            return false;
        }

        $stop_links = array(
            '@^javascript\:void\(0\);?$@',
            '@^#.*@',
            '@^void\(0\);$@'
        );

        foreach ($stop_links as $ptrn) {
            if (preg_match($ptrn, $url)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Change url to absolute path if currently relative
     * @param  string $url
     * @return string
     */
    public function normalizeLink($url)
    {
        $url = preg_replace('@#.*$@', '', $url);
        $url = preg_replace('/[.]+\//', '', $url);
        $parse = parse_url($url);
        if (!empty($url)) {
            if (empty($parse['scheme']) && !empty($parse['host'])) {
                return 'http:' . $url;
            }
            if (!empty($parse['host'])) {
                if (!empty($parse['path'])) {
                    if ($parse['path'] === '/') {
                        return $parse['scheme'] . '://' . $parse['host'];
                    } elseif (substr($parse['path'], -1) === '/') {
                        return rtrim($url, '/');
                    }
                }
                return $url;
            } elseif (empty($parse['host'])) {
                if (!empty($parse['path'])) {
                    if ($parse['path'][0] !== '/') {
                        return $this->baseUrl . '/' . $parse['path'];
                    } elseif (substr($parse['path'], -1) === '/') {
                        return rtrim($url, '/');
                    } else {
                        return $this->baseUrl . $parse['path'];
                    }
                }
            }
        }
        return $url;
    }

    /**
     * Check url is external url or not
     * @param  string $url
     * @return boolean
     */
    public function checkIfExternal($url)
    {
        $baseUrlTrimmed = str_replace(array('http://', 'https://'), '', $this->baseUrl);
        if (preg_match("@http(s)?\://$baseUrlTrimmed@", $url)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get list of url from the web
     * @return array
     */
    public function getSiteUrl()
    {
        return $this->siteUrl;
    }

    /**
     * Get list of css from the web
     * @return array
     */
    public function getSiteCss()
    {
        return $this->siteCss;
    }

    /**
     * Get list of js from the web
     * @return array
     */
    public function getSiteJs()
    {
        return $this->siteJs;
    }

    /**
     * Get number of broken links
     * @return int
     */
    public function getSiteBrokenLink()
    {
        return $this->siteBrokenLink;
    }

    /**
     * Get number of file links
     * @return int
     */
    public function getSiteFile()
    {
        return $this->siteFile;
    }

    /**
     * Set website url for crawling
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        if (strpos($baseUrl, 'http') === false) {
            $this->baseUrl = 'http://' . $baseUrl;
        } else {
            $this->baseUrl = $baseUrl;
        }
    }

    /**
     * Set content-type headers manually
     * @param boolean $value
     */
    public function setContentType($value)
    {
        $this->contentType = $value;
    }

    /**
     * Get main url of website
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
    
    /**
     * Function to trigger start crawling a website
     * @return void
     */
    public function start()
    {
        $baseUrl = $this->normalizeLink($this->getBaseUrl());

        $this->crawl($baseUrl);
    }

    protected function crawl($url)
    {
        // echo $url . ' : ' . count($this->siteUrl) . "\n";
        $responseUrl = $this->doRequest($url);
        if (!is_null($responseUrl)) {
            $listCss = $responseUrl->filterXPath('//*[@rel="stylesheet"]')->extract('href');
            $this->getAllOtherLink($listCss, $this->siteCss);

            $listJs = $responseUrl->filter('script')->extract('src');
            $this->getAllOtherLink($listJs, $this->siteJs);

            $listUrl = $responseUrl->filter('a')->extract('href');
            $this->getAllUrlRecursive($listUrl);
        }
    }

    protected function getAllUrlRecursive($lists)
    {
        foreach ($lists as $list) {
            if ($this->checkIfCrawlable($list)) {
                $list = $this->normalizeLink($list);
                if ($this->checkNotInList($list, $this->siteUrl) && !$this->checkIfExternal($list)) {
                    $this->crawl($list);
                }
            }
        }
    }

    protected function getAllOtherLink($lists, &$siteLink)
    {
        foreach ($lists as $list) {
            if ($this->checkIfCrawlable($list)) {
                $list = $this->normalizeLink($list);
                if (!empty($list) && !in_array($list, $siteLink) && !$this->checkIfExternal($list)) {
                    array_push($siteLink, $list);
                }
            }
        }
    }

    public function checkNotInList($url)
    {
        if (!in_array($url, $this->siteUrl) && !in_array($url, $this->siteBrokenLink)) {
            return true;
        }
        return false;
    }

    protected function checkStatusCode()
    {
        if ($this->client->getResponse()->getStatus() === 200) {
            return true;
        } elseif ($this->client->getResponse()->getStatus() >= 400) {
            return false;
        }
    }

    public function doRequest($url, $try = null)
    {
        if (is_null($try)) {
            $try = $this->retry;
        }

        if ($try < 0) {
            return null;
        }

        try {
            $responseUrl = $this->client->request('GET', $url);
            if ($this->checkStatusCode() && $this->contentType) {
                array_push($this->siteUrl, $url);
                return $responseUrl;
            }
            if (!$this->contentType) {
                array_push($this->siteFile, $url);
                return null;
            }
            if (!$this->checkStatusCode()) {
                array_push($this->siteBrokenLink, $url);
                return null;
            }
        } catch (\Exception $e) {
            $try--;
            return $this->doRequest($url, $try);
        }
    }
}
