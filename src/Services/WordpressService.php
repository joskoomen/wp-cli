<?php

namespace Ypa\Wordpress\Cli\Services;

use GuzzleHttp\Client;

class WordpressService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @return string
     */
    public function getPluginZipName($param): string
    {
        return 'https://downloads.wordpress.org/plugin/' . $param . '.zip';
    }

    /**
     * @return string
     */
    public function downloadPlugin($url): string
    {
        $response = $this->client->get($url);
        return $response->getBody();
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function checkLatestVersion(): string
    {
        $json = json_decode(file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), true, 512, JSON_THROW_ON_ERROR);
        return $json['offers'][0]['version'];
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function getWordpressByVersion(string $version): string
    {
        switch ($version) {
            default:
                $filename = 'wordpress-' . $version . '.zip';
                break;
            case 'latest':
                $filename = 'latest.zip';
                break;
        }

        $response = (new Client)->get('https://wordpress.org/' . $filename);
        return $response->getBody();
    }

    public function getWpSaltSections(): string
    {
        return file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');
    }
}
