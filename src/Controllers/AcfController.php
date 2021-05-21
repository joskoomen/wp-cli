<?php

namespace Ypa\Wordpress\Cli\Controllers;

use Ypa\Wordpress\Cli\Resources\AcfSyncResource;
use Symfony\Component\Console\Output\OutputInterface;

class AcfController
{

    public function start(OutputInterface $output, string $appDirectory): void
    {
        new AcfSyncResource($output, $appDirectory);
    }
}
