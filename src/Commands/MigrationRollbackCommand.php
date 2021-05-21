<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationRollbackCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('db:rollback')
            ->setDescription('Rollback the last or to a specific migration')
            ->addOption('--target', '-t', InputOption::VALUE_REQUIRED, 'The version number to rollback to')
            ->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to rollback to')
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force rollback to ignore breakpoints')
            ->addOption('--dry-run', '-x', InputOption::VALUE_NONE, 'Dump query to standard output instead of executing it')
            ->addOption('--fake', null, InputOption::VALUE_NONE, "Mark any rollbacks selected as run, but don\'t actually execute them")
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The environment to migrate to');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = getcwd();
        $this->writeIntro($output, '🤏', 'Start Rollback.');

        $string = 'php vendor/bin/phinx rollback -c migrations.php';
        if ($input->hasOption('--target')) {
            $string .= ' --target ' . $input->getOption('--target');
        }
        if ($input->hasOption('--environment')) {
            $string .= ' -e ' . $input->getOption('--e');
        } else {
            $string .= ' -e local';
        }
        if ($input->hasOption('--date')) {
            $string .= ' --date ' . $input->getOption('--date');
        }
        if ($input->hasOption('--force')) {
            $string .= ' --force';
        }
        if ($input->hasOption('--dry-run')) {
            $string .= ' --dry-run';
        }
        if ($input->hasOption('--fake')) {
            $string .= ' --fake';
        }

        $commands = [$string];

        $this->runCommands($output, $directory, $commands);
        $this->writeClose($output, '🎃', 'Rolled back!');
    }
}
