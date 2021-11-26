<?php

namespace JosKoomen\Wordpress\Cli\Resources;


use Symfony\Component\DomCrawler\Crawler;
use JosKoomen\Wordpress\Cli\Controllers\PluginsController;
use JosKoomen\Wordpress\Cli\Services\WordpressService;
use JosKoomen\Wordpress\Cli\Traits\CmdTrait;
use JosKoomen\Wordpress\Cli\Traits\DirectoryTrait;
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
