<?php

namespace Ypa\Wordpress\Cli\Services;

use GuzzleHttp\Client;

class PackageService
{
    private Client $client;
    private string $url;

    public function __construct()
    {
        $this->client = new Client();
        $this->url = 'https://packages.ypa.agency/hot-sauce';
    }

    /**
     * @return string
     */
    public function getWordpressHotSauce(): string
    {
        $response = $this->client->get($this->url . '-wp.zip');
        return $response->getBody()->getContents();
    }
}
