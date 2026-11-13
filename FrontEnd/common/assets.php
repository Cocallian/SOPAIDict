<?php
// Links to the script and style files. The file's last modified time is added, so a browser fetches the new copy after a change.

function scriptTag(string $file): void
    {
        echo '<script src="' . $file . '?v=' . filemtime(dirname(__DIR__) . "/" . $file) . '" defer></script>' . "\n    ";
    }

function styleTag(string $file): void
    {
        echo '<link rel="stylesheet" href="' . $file . '?v=' . filemtime(dirname(__DIR__) . "/" . $file) . '">' . "\n    ";
    }
