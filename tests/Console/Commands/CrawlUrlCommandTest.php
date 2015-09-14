<?php

namespace SuitTests\Console\Commands;

use Mockery;
use Suitcoda\Console\Commands\CrawlUrlCommand;
use Illuminate\Console\Command;
use SuitTests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Test Suitcoda\Console\Commands\CrawlUrlCommand
 */

class CrawlUrlCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * test continue if user success save to database
     */
    public function testCommand()
    {
        $this->artisan('crawl:url', [
            'url' => 'suitmedia.com'
        ]);
    }
}
