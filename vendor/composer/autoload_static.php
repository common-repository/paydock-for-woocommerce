<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5def1875a6e5e3afb49cf3519ebccdef
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Paydock\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Paydock\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5def1875a6e5e3afb49cf3519ebccdef::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5def1875a6e5e3afb49cf3519ebccdef::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5def1875a6e5e3afb49cf3519ebccdef::$classMap;

        }, null, ClassLoader::class);
    }
}