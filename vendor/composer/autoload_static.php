<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55bc2b2bb7a508328803d16686ec1745
{
    public static $files = array (
        '241d2b5b9c1e680c0770b006b0271156' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v4p9.php',
    );

    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'setasign\\Fpdi\\' => 14,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'setasign\\Fpdi\\' => 
        array (
            0 => __DIR__ . '/..' . '/setasign/fpdi/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $classMap = array (
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit55bc2b2bb7a508328803d16686ec1745::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55bc2b2bb7a508328803d16686ec1745::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit55bc2b2bb7a508328803d16686ec1745::$classMap;

        }, null, ClassLoader::class);
    }
}
