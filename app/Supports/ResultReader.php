<?php

namespace Suitcoda\Supports;

use Suitcoda\Model\Issue;
use Webmozart\Json\FileNotFoundException;
use Webmozart\Json\JsonDecoder;

class ResultReader
{
    protected $job;

    protected $decoder;

    protected $issue;

    /**
     * Class constructor
     *
     * @param JsonDecoder $decoder []
     * @param Issue       $issue   []
     */
    public function __construct(JsonDecoder $decoder, Issue $issue)
    {
        $this->decoder = $decoder;
        $this->issue = $issue;
    }

    /**
     * Set job to read
     *
     * @param JobInspect $job []
     * @return void
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * Run result reader
     *
     * @return bool
     */
    public function run()
    {
        preg_match('/public\/files\/(.*)(url-)/', $this->job->command_line, $match);
        $scope = $this->job->scope->name;
        switch ($scope) {
            case 'seo':
                return $this->seoResultReader($match[0] . $this->job->url->id . '/resultSEO.json');
            case 'backendSeo':
                return $this->seoResultReader($match[0] . $this->job->url->id . '/resultBackendSEO.json');
            case 'html':
                return $this->htmlResultReader($match[0] . $this->job->url->id . '/resultHTML.json');
            case 'css':
                return $this->cssResultReader($match[0] . $this->job->url->id . '/resultCSS.json');
            case 'js':
                return $this->jsResultReader($match[0] . $this->job->url->id . '/resultJS.json');
            case 'socialMedia':
                return $this->socialMediaResultReader($match[0] . $this->job->url->id . '/resultSocmed.json');
            case 'gPagespeedDesktop':
                return $this->gPagespeedResultReader($match[0] . $this->job->url->id . '/resultPagespeedDesktop.json');
            case 'gPagespeedMobile':
                return $this->gPagespeedResultReader($match[0] . $this->job->url->id . '/resultPagespeedMobile.json');
            case 'ySlow':
                return $this->ySlowResultReader($match[0] . $this->job->url->id . '/resultYSlow.json');
        }
    }

    /**
     * Result reader for seo
     *
     * @param  string $path []
     * @return bool
     */
    public function seoResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);

            foreach ($jsonData->checking as $checking) {
                $issue = $this->issue->newInstance();
                $issue->type = strtolower($checking->error);
                $issue->description = $checking->desc;
                $issue->url = $jsonData->url;
                $issue->inspection()->associate($this->job->inspection);
                $issue->jobInspect()->associate($this->job);
                $issue->scope()->associate($this->job->scope);
                $issue->save();
            }
            $this->job->update(['issue_count' => count($jsonData->checking), 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for html
     *
     * @param  string $path []
     * @return bool
     */
    public function htmlResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);

            foreach ($jsonData->checking as $checking) {
                $issue = $this->issue->newInstance();
                $issue->type = strtolower($checking->type);
                $issue->description = $checking->desc;
                $issue->issue_line = $checking->line;
                $issue->url = $jsonData->url;
                $issue->inspection()->associate($this->job->inspection);
                $issue->jobInspect()->associate($this->job);
                $issue->scope()->associate($this->job->scope);
                $issue->save();
            }
            $this->job->update(['issue_count' => count($jsonData->checking), 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for css
     *
     * @param  string $path []
     * @return bool
     */
    public function cssResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);

            foreach ($jsonData->checking as $checking) {
                $issue = $this->issue->newInstance();
                $issue->type = strtolower($checking->messageType);
                $issue->description = $checking->messageMsg;
                if (isset($checking->messageLine)) {
                    $issue->issue_line = $checking->messageLine;
                }
                $issue->url = $jsonData->url;
                $issue->inspection()->associate($this->job->inspection);
                $issue->jobInspect()->associate($this->job);
                $issue->scope()->associate($this->job->scope);
                $issue->save();
            }
            $this->job->update(['issue_count' => count($jsonData->checking), 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for js
     *
     * @param  string $path []
     * @return bool
     */
    public function jsResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);

            foreach ($jsonData->checking as $checking) {
                if (isset($checking->id)) {
                    $issue = $this->issue->newInstance();
                    $issue->type = strtolower(trim($checking->id, "()"));
                    $issue->description = $checking->reason;
                    $issue->issue_line = $checking->line;
                    $issue->url = $jsonData->url;
                    $issue->inspection()->associate($this->job->inspection);
                    $issue->jobInspect()->associate($this->job);
                    $issue->scope()->associate($this->job->scope);
                    $issue->save();
                }
            }
            $this->job->update(['issue_count' => count($jsonData->checking), 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for social media
     *
     * @param  string $path []
     * @return bool
     */
    public function socialMediaResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);
            $counter = 0;

            foreach ($jsonData->checking as $checking) {
                foreach ($checking->message as $message) {
                    $issue = $this->issue->newInstance();
                    $issue->type = strtolower($message->error);
                    $issue->description = $message->desc;
                    $issue->url = $jsonData->url;
                    $issue->inspection()->associate($this->job->inspection);
                    $issue->jobInspect()->associate($this->job);
                    $issue->scope()->associate($this->job->scope);
                    $issue->save();
                    $counter++;
                }
            }
            $this->job->update(['issue_count' => $counter, 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for Google Pagespeed
     *
     * @param  string $path []
     * @return bool
     */
    public function gPagespeedResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);
            $counter = 0;

            foreach ($jsonData->formattedResults->ruleResults as $checking) {
                if ($checking->ruleImpact > 0) {
                    $issue = $this->issue->newInstance();
                    $issue->type = strtolower($this->getPSIErrorType($checking->ruleImpact));
                    $issue->description = $checking->localizedRuleName . " :\n" .
                                          $this->getPSIErrorDescription($checking->urlBlocks);
                    $issue->url = $jsonData->id;
                    $issue->inspection()->associate($this->job->inspection);
                    $issue->jobInspect()->associate($this->job);
                    $issue->scope()->associate($this->job->scope);
                    $issue->save();
                    $counter++;
                }
            }
            $this->job->update(['issue_count' => $counter, 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Result reader for YSlow
     *
     * @param  string $path []
     * @return bool
     */
    public function ySlowResultReader($path)
    {
        try {
            $jsonData = $this->decoder->decodeFile($path);
            
            foreach ($jsonData->checking as $checking) {
                $issue = $this->issue->newInstance();
                $issue->type = strtolower($checking->error);
                $issue->description = $this->getYslowErrorDesc($checking);
                $issue->url = $jsonData->url;
                $issue->inspection()->associate($this->job->inspection);
                $issue->jobInspect()->associate($this->job);
                $issue->scope()->associate($this->job->scope);
                $issue->save();
            }
            $this->job->update(['issue_count' => count($jsonData->checking), 'status' => 2]);
            return false;
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * Get Yslow error description
     *
     * @param  object $checking []
     * @return string
     */
    protected function getYslowErrorDesc($checking)
    {
        $desc = $checking->name . ":\n" .
                $checking->desc . "\n";
        if (!empty($checking->code)) {
            foreach ($checking->code as $code) {
                $desc .= urldecode($code) . "\n";
            }
        }
        return $desc;
    }

    /**
     * Get Pagespeed error type
     *
     * @param  int $ruleImpact []
     * @return string
     */
    public function getPsiErrorType($ruleImpact)
    {
        if ($ruleImpact == 10) {
            return 'Error';
        }
        return 'Warning';
    }

    /**
     * Get Pagespeed full error description
     *
     * @param  object $urlBlocks []
     * @return string
     */
    public function getPsiErrorDescription($urlBlocks)
    {
        $result = '';
        foreach ($urlBlocks as $block) {
            if (isset($block->header)) {
                $desc = $this->getDescFormatted($block->header);
                $result = $result . $desc . "\n";
            }
            if (isset($block->urls)) {
                foreach ($block->urls as $url) {
                    $desc = $this->getDescFormatted($url->result);
                    $result = $result . $desc . "\n";
                }
            }
        }
        return $result;
    }

    /**
     * Get pattern for Pagespeed result format getter
     *
     * @param  int $count []
     * @return string
     */
    public function getPatterns($count)
    {
        $result = '';

        while ($count) {
            $result .= '(.*)';
            $count--;

            if ($count != 0) {
                $result .= '\|';
            }
        }
        return '/' . $result . '/';
    }

    /**
     * Get Pagespeed error description in custom format
     *
     * @param  object $descObject []
     * @return string
     */
    public function getDescFormatted($descObject)
    {
        if (isset($descObject->args)) {
            $value = implode('|', array_pluck($descObject->args, 'value'));
            return preg_replace($this->getPatterns(count($descObject->args)), $descObject->format, $value);
        } else {
            return $descObject->format;
        }
    }
}
