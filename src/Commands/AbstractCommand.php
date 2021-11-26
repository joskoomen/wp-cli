<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use JosKoomen\Wordpress\Cli\Traits\CmdTrait;
use JosKoomen\Wordpress\Cli\Traits\CreatorTrait;
use JosKoomen\Wordpress\Cli\Traits\DirectoryTrait;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    use CreatorTrait, DirectoryTrait, CmdTrait;
}
