<?php

namespace Ypa\Wordpress\Cli\Commands;

use Ypa\Wordpress\Cli\Traits\CmdTrait;
use Ypa\Wordpress\Cli\Traits\CreatorTrait;
use Ypa\Wordpress\Cli\Traits\DirectoryTrait;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    use CreatorTrait, DirectoryTrait, CmdTrait;
}
