<?php

namespace Bnomei;

class Imageoptim
{
    private static $instance = null;
    public static function instance()
    {
        $apikey = option('bnomei.thumbimageoptim.apikey');
        if ($apikey && !static::$instance) {
            static::$instance = new \ImageOptim\API($apikey);
        }
        return static::$instance;
    }

    private static $kirbyThumbsComponent = null;
    public static function beforeRegisterComponent()
    {
        if (!static::$kirbyThumbsComponent) {
            static::$kirbyThumbsComponent = kirby()->component('thumb');
        }
    }

    public static function kirbyThumb($src, $dst, $options)
    {
        if (static::$kirbyThumbsComponent && \is_callable(static::$kirbyThumbsComponent)) {
            return static::$kirbyThumbsComponent($src, $dst, $options);
        }
        return null;
    }

    public static function thumb($src, $dst, $options)
    {
        $root = (new \Kirby\Cms\Filename($src, $dst, $options))->toString();
        if (\file_exists($root) === true && \filemtime($root) >= \filemtime($src)) {
            return $root;
        }

        $api = static::instance();
        if (!option('bnomei.thumbimageoptim.optimize') || !$api) {
            return static::kirbyThumb($src, $dst, $options);
        }

        $success = false;
        $defaults = option('bnomei.thumbimageoptim.defaults');
        $settings = array_merge($options, $defaults);

        try {
            // https://github.com/ImageOptim/php-imageoptim-api
            $request = null;
            if (static::is_localhost()) {
                // upload
                $request = $api->imageFromPath($src);
            } else {
                // request download

                // TODO: splitting path is a hack. might not be underscore forever.
                $path = explode('/', ltrim(str_replace(kirby()->roots()->content(), '', \dirname($src)), '/'));
                $pathO = array_map(function ($v) {
                    $pos = strpos($v, '_');
                    if ($pos === false) {
                        $v;
                    } else {
                        return substr($v, $pos+1);
                    }
                }, $path);
                $pathO = implode('/', $pathO);
                $page = page($pathO);

                if ($img = $page->files(\pathinfo($src, PATHINFO_BASENAME))->first()) {
                    $url = $img->url();
                }
                $request = $api->imageFromURL($url);
            }
            if ($request) {
                $request = $request->resize(
                $settings['width'],
                $settings['height'],
                $settings['crop'] == 1 ? 'crop' : 'scale-down'
            )->quality(
                $settings['io_quality']
            )
            ->dpr(intval($settings['io_dpr']));

                if ($tl = option('bnomei.thumbimageoptim.timelimit')) {
                    set_time_limit(intval($tl));
                }
                $success = \Kirby\Toolkit\F::write($dst, $request->getBytes());
            }
        } catch (Exception $ex) {
            new \Kirby\Exception($ex->getMessage());
        }

        if ($success) {
            return $dst;
        }
        return static::kirbyThumb($src, $dst, $options);
    }

    private static function is_localhost()
    {
        $whitelist = array( '127.0.0.1', '::1' );
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return true;
        }
    }
}
