<?php

namespace Webkul\Installer\Events;

use Symfony\Component\Console\Output\ConsoleOutput;

class ComposerEvents
{
    /**
     * Post create project.
     *
     * @return void
     */
    public static function postCreateProject()
    {
        $output = new ConsoleOutput;

        $output->writeln(file_get_contents(__DIR__.'/../Templates/on-boarding.php'));
    }

    /**
     * Kept for backwards compatibility; the console no longer prints any third-party promotion.
     *
     * @return string
     */
    public static function cloudHostingBox()
    {
        return '';
    }
}
