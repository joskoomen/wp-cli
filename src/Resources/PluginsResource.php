<?php

namespace Ypa\Wordpress\Cli\Resources;


use Symfony\Component\DomCrawler\Crawler;
use Ypa\Wordpress\Cli\Controllers\PluginsController;
use Ypa\Wordpress\Cli\Services\WordpressService;
use Ypa\Wordpress\Cli\Traits\CmdTrait;
use Ypa\Wordpress\Cli\Traits\DirectoryTrait;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsResource
{
    use DirectoryTrait, CmdTrait;

    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function listVersions(WordpressService $wordpressService, string $pluginName): void
    {
        $output = $this->output;
        $html = $wordpressService->getPluginAdvancedData($pluginName);
        $crawler = new Crawler($html);
        $select = $crawler->filter('.previous-versions');
        $select->children('option')->first()->nextAll()->each(function (Crawler $node) use ($output) {
            $this->writeMessage($output, '👉', $node->text());
        });
    }
}
