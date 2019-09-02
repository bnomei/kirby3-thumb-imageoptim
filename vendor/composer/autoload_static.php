<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc14e89fafce21e20f0b2ebc93978c796
{
    public static $prefixLengthsPsr4 = array (
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
        'I' => 
        array (
            'ImageOptim\\' => 11,
        ),
        'B' => 
        array (
            'Bnomei\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
        'ImageOptim\\' => 
        array (
            0 => __DIR__ . '/..' . '/imageoptim/imageoptim/src',
        ),
        'Bnomei\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Bnomei\\Imageoptim' => __DIR__ . '/../..' . '/classes/Imageoptim.php',
        'Bnomei\\Thumb' => __DIR__ . '/../..' . '/classes/Thumb.php',
        'ImageOptim\\API' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/API.php',
        'ImageOptim\\APIException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/APIException.php',
        'ImageOptim\\AccessDeniedException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/AccessDeniedException.php',
        'ImageOptim\\FileRequest' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/FileRequest.php',
        'ImageOptim\\InvalidArgumentException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/InvalidArgumentException.php',
        'ImageOptim\\NetworkException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/NetworkException.php',
        'ImageOptim\\NotFoundException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/NotFoundException.php',
        'ImageOptim\\OriginServerException' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/OriginServerException.php',
        'ImageOptim\\Request' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/Request.php',
        'ImageOptim\\URLRequest' => __DIR__ . '/..' . '/imageoptim/imageoptim/src/URLRequest.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc14e89fafce21e20f0b2ebc93978c796::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc14e89fafce21e20f0b2ebc93978c796::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc14e89fafce21e20f0b2ebc93978c796::$classMap;

        }, null, ClassLoader::class);
    }
}
