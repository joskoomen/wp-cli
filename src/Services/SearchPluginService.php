<?php

namespace Ypa\Wordpress\Cli\Services;

use GuzzleHttp\Client;

class SearchPluginService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function searchPlugin(string $pluginQuery)
    {
        $api_params = [
            'user_agent' => 'YPA/WP-CLI/1.0',
            'request[search]' => $pluginQuery,
            'request[page]' => 1,
            'request[per_page]' => 1,

            'request[fields]' => [
                'name' => true,
                'author' => true,
                'slug' => true,
                'downloadlink' => true,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'description' => false,
                'active_installs' => false,
                'short_description' => false,
                'donate_link' => false,
                'tags' => false,
                'sections' => false,
                'homepage' => false,
                'added' => false,
                'last_updated' => false,
                'compatibility' => false,
                'tested' => false,
                'requires' => false,
                'versions' => false,
                'support_threads' => false,
                'support_threads_resolved' => false,
            ],
        ];

        $api_url = 'http://api.wordpress.org/plugins/info/1.1/?action=query_plugins';
        $packaged_params = $api_params;
        $packaged_params['request']['search'] = $pluginQuery;
        $packaged_params = http_build_query($packaged_params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $packaged_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // hmm?
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // hmm?
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $content_json_maybe = curl_exec($ch);
        $error = curl_error($ch);

        if (!empty($error)) {
            echo "Error: $error\n";
            $result = curl_getinfo($ch);
        } else {
            $result = @json_decode($content_json_maybe, true);
        }

        curl_close($ch);

        return $result;

    }


}
