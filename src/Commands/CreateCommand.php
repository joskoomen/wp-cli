<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use JosKoomen\Wordpress\Cli\Controllers\WordpressController;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->setName('create')
            ->setDescription('Create a new Wordpress')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('wpv', 'w', InputOption::VALUE_REQUIRED, 'An optional version for your wordpress installation')
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Optionally install plugins too');

        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $appDirectory = $this->getDirectory($input);
        $this->verifyApplicationDoesntExist($appDirectory);
        $this->startCrafting($input, $output, $appDirectory);

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \JsonException
     */
    protected function startCrafting(InputInterface $input, OutputInterface $output, string $appDirectory): void
    {
        $helper = $this->getHelper('question');
        $wpJsonFile = $this->getWpJsonPath($appDirectory);

        $file = @file_get_contents($wpJsonFile) ?? '';
        $projectName = '';
        if (!empty($file)) {
            $json = @json_decode($file, true, 512, JSON_THROW_ON_ERROR);
            $projectName = $json['name'] ?? '';
        }

        $hasName = !empty($projectName);

        if (!$hasName) {
            $nameQuestion = new Question('<comment>Enter the name of your project 👉 </comment> ');
            $projectName = $helper->ask($input, $output, $nameQuestion);
        }

        $dbHostQuestion = new Question('<comment>Enter the DB_HOST of your project </comment> (default = localhost) 👉 ', '127.0.0.1');
        $dbNameQuestion = new Question('<comment>Enter the DB_DATABASE of your project 👉 </comment> ');
        $dbUserQuestion = new Question('<comment>Enter the DB_USERNAME of your project </comment> (default = root) 👉 ', 'root');
        $dbPassQuestion = new Question('<comment>Enter the DB_PASSWORD of your project </comment> (default = root) 👉 ', 'root');
        $dbTablePrefixQuestion = new Question('<comment>Enter the DB_TABLE_PREFIX of your project </comment> (default = wp_) 👉 ', 'wp_');
        $adminEmailQuestion = new Question('<comment>Enter your e-mail address (for wp-admin) </comment> (default = noreply@joskoomen.nl) 👉 ', 'noreply@joskoomen.nl');
        $dbHost = $helper->ask($input, $output, $dbHostQuestion);
        $dbName = $helper->ask($input, $output, $dbNameQuestion);
        $dbUser = $helper->ask($input, $output, $dbUserQuestion);
        $dbPass = $helper->ask($input, $output, $dbPassQuestion);
        $dbPrefix = $helper->ask($input, $output, $dbTablePrefixQuestion);
        $adminEmail = $helper->ask($input, $output, $adminEmailQuestion);
        $creator = new WordpressController($projectName, $adminEmail, $dbHost, $dbName, $dbUser, $dbPass, $dbPrefix);

        $this->writeIntro($output, '🍔', "Ok, let's go! Crafting your WordPress project...");

        try {
            $creator->start($input, $output);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Oops! ... ' . $e->getMessage() . '\n' . $e->getTraceAsString());
        }
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param string $directory
     *
     * @return void
     */
    private function verifyApplicationDoesntExist(string $appDirectory): void
    {
        $wpDir = $this->getWordpressDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-content';
        if ((is_dir($wpDir) || ($wpDir !== getcwd() && is_file($wpDir)))) {
            throw new \RuntimeException('Wordpress already installed!');
        }
    }
}
