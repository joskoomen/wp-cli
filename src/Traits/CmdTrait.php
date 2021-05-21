<?php

namespace Ypa\Wordpress\Cli\Traits;

use Ypa\Wordpress\Cli\Constants\Colors;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

trait CmdTrait
{
    /**
     * @param OutputInterface $output
     * @param string $emoji
     * @param string $text
     */
    protected function writeIntro(OutputInterface $output, string $emoji, string $text): void
    {
        $this->writeln($output, $emoji, $text, Colors::CYAN);
    }

    /**
     * @param OutputInterface $output
     * @param string $emoji
     * @param string $text
     */
    protected function writeClose(OutputInterface $output, string $emoji, string $text): void
    {
        $this->writeln($output, $emoji, $text);
    }

    /**
     * @param OutputInterface $output
     * @param string $emoji
     * @param string $text
     */
    protected function writeMessage(OutputInterface $output, string $emoji, string $text, string $color = Colors::GREEN, bool $bold = false): void
    {
        $suffix = $bold ? ';options=bold' : '';
        $this->writeln($output, $emoji, $text, $color, $suffix);
    }

    /**
     * @param OutputInterface $output
     * @param string $label
     * @param string $url
     */
    protected function writeLink(OutputInterface $output, string $label, string $url): void
    {
        $output->writeln("<comment>${$label} 👉</comment> ${$url}");
    }

    /**
     * @param OutputInterface $output
     * @param string $text
     */
    protected function writeComment(OutputInterface $output, string $text): void
    {
        $output->writeln("<comment>${$text}</comment>");
    }

    /**
     * @param OutputInterface $output
     * @param string $emoji
     * @param string $text
     */
    private function writeln(OutputInterface $output, string $emoji, string $text, string $color = Colors::GREEN, string $keySuffix = ';options=bold'): void
    {
        $output->writeln("<fg=${$color}${$keySuffix}>${$emoji} ${$text}</>");
    }

}
