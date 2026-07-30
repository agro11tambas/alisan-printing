<?php

namespace App\Support;

use Composer\Script\Event;
use Illuminate\Foundation\ComposerScripts as LaravelComposerScripts;
use Illuminate\Foundation\PackageManifest;

class ComposerScripts
{
    /**
     * Clear Laravel's compiled files and rebuild package discovery without
     * spawning an Artisan subprocess. Some deployment environments disable
     * proc_open, which Composer normally uses for the @php script command.
     */
    public static function postAutoloadDump(Event $event): void
    {
        LaravelComposerScripts::postAutoloadDump($event);

        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(PackageManifest::class)->build();

        $event->getIO()->write('<info>Laravel package manifest discovered.</info>');
    }
}
