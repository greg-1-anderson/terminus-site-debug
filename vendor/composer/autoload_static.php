<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit45611277683e15d884cc0827d558d86b
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Seld\\CliPrompt\\' => 15,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Pantheon\\TerminusSiteLogs\\' => 26,
        ),
        'L' => 
        array (
            'League\\CLImate\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Seld\\CliPrompt\\' => 
        array (
            0 => __DIR__ . '/..' . '/seld/cli-prompt/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Pantheon\\TerminusSiteLogs\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'League\\CLImate\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/climate/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit45611277683e15d884cc0827d558d86b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit45611277683e15d884cc0827d558d86b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit45611277683e15d884cc0827d558d86b::$classMap;

        }, null, ClassLoader::class);
    }
}
