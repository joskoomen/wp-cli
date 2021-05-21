<?php

namespace Ypa\Wordpress\Cli\Resources;


use Ypa\Wordpress\Cli\Traits\DirectoryTrait;
use Symfony\Component\Console\Output\OutputInterface;

class AcfSyncResource
{
    use DirectoryTrait;

    public function __construct(OutputInterface $output, string $appDirectory, string $acfLicence = '')
    {
        $wp = $this->getWordpressDirectory($appDirectory) . DIRECTORY_SEPARATOR;
        $wpIncludes = $wp . 'wp-includes' . DIRECTORY_SEPARATOR;
        $acfPlugin = $this->getPluginsDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'advanced-custom-fields-pro';
        $acfPluginAdmin = $acfPlugin . DIRECTORY_SEPARATOR . 'pro' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
        $acfIncludes = $acfPlugin . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

        try {
            require $wp . 'wp-load.php';
            require_once $wp . 'wp-config.php';
            require_once $wpIncludes . 'wp-db.php';
            require_once $acfPluginAdmin . 'admin-updates.php';
            require_once $acfIncludes . 'admin' . DIRECTORY_SEPARATOR . 'admin-notices.php';
            require_once $acfIncludes . 'acf-field-group-functions.php';
            require_once $acfIncludes . 'api' . DIRECTORY_SEPARATOR . 'api-helpers.php';
            require_once $acfIncludes . 'acf-helper-functions.php';
            require_once $acfIncludes . 'acf-field-functions.php';

        } catch (\Exception $e) {
            throw new \RuntimeException('Make sure the ACF plugin is available');
        }


        if (!empty($acfLicence)) {
            $acfAdminUpdates = new \ACF_Admin_Updates();
            $_POST['acf_pro_licence'] = $acfLicence;
            $acfAdminUpdates->activate_pro_licence();
        }

        $sync = [];
        $groups = acf_get_field_groups();

        if (empty($groups)) {
            $output->writeln('<fg=green;options=bold>👀 No Acf groups available.</>');
            return;
        }

        foreach ($groups as $group) {
            // vars
            $local = acf_maybe_get($group, 'local', false);
            $modified = acf_maybe_get($group, 'modified', 0);
            $private = acf_maybe_get($group, 'private', false);

            if ($private) {
                continue;
            }

            if ($local !== 'json') {
                continue;
            }

            if (!$group['ID']) {
                $sync[$group['key']] = $group;
            } elseif ($modified && $modified > get_post_modified_time('U', true, $group['ID'], true)) {
                $sync[$group['key']] = $group;
            }
        }

        if (empty($sync)) {
            $output->writeln('<fg=green;options=bold>👀 No Acf files available to sync.</>');
            return;
        }

        acf_disable_filters();
        acf_enable_filter('local');
        acf_update_setting('json', false);

        foreach ($sync as $group) {
            $group['fields'] = acf_get_fields($group);
            $output->writeln('<fg=green>🦄 Syncing "' . $group['title'] . '"</>');
            acf_import_field_group($group);
        }
        $output->writeln('<fg=green;options=bold>🚀️ Syncing done!</>');
    }
}
