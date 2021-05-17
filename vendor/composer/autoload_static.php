<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb970f1582cd12ae1beda51035da93dd4
{
    public static $files = array (
        'be8785f285476d960a9374d1a827f21a' => __DIR__ . '/..' . '/pinkcrab/hook-loader/tests/Fixtures/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Container\\' => 14,
            'PinkCrab\\Perique\\' => 17,
            'PinkCrab\\Loader\\' => 16,
        ),
        'D' => 
        array (
            'Dice\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/container/src',
        ),
        'PinkCrab\\Perique\\' => 
        array (
            0 => __DIR__ . '/..' . '/pinkcrab/perique-framework-core/src',
        ),
        'PinkCrab\\Loader\\' => 
        array (
            0 => __DIR__ . '/..' . '/pinkcrab/hook-loader/src',
        ),
        'Dice\\' => 
        array (
            0 => __DIR__ . '/..' . '/level-2/dice',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb970f1582cd12ae1beda51035da93dd4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb970f1582cd12ae1beda51035da93dd4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
