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

    private static function log(string $msg = '', string $level = 'info', array $context = []):bool {
        $log = option('bnomei.thumbimageoptim.log');
        if($log && is_callable($log)) {
            if (!option('debug') && $level == 'debug') {
                // skip but...
                return true;
            } else {
                return $log($msg, $level, $context);
            }
        }
        return false;
    }

    private static $kirbyThumbsComponent = null;
    public static function beforeRegisterComponent()
    {
        if (!static::$kirbyThumbsComponent) {
            static::log('beforeRegisterComponent', 'debug');
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
        // $root = (new \Kirby\Cms\Filename($src, $dst, $options))->toString();
        if (\file_exists($dst) == true && \filemtime($dst) >= \filemtime($src)) {
            static::log('exists', 'debug', [
                'src' => $src,
                'src-filemtime' => date('c', \filemtime($src)),
                'dst' => $dst,
                'dst-filemtime' => date('c', \filemtime($dst)),
            ]);
            return $dst;
        } else {
            \Kirby\Toolkit\F::copy($src, $dst);
            static::log('copy', 'debug', [
                'src' => $src,
                'src-filemtime' => date('c', \filemtime($src)),
                'dst' => $dst,
                'dst-filemtime' => date('c', \filemtime($dst)),
            ]);
        }

        $api = static::instance();
        if (!option('bnomei.thumbimageoptim.optimize') || !$api) {
            static::log('kirbyThumb:early', 'debug', [
                'src' => $src,
                'dst' => $dst,
                'options' => $options,
            ]);
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
                static::log('imageFromPath', 'debug', [
                    'src' => $src,
                    'dst' => $dst,
                    'options' => $options,
                ]);
            } else {
                // request download

                // TODO: splitting path is a hack. might not be underscore forever.
                $path = explode('/', ltrim(str_replace(kirby()->roots()->content(), '', \dirname($src)), '/'));
                $pathO = array_map(function ($v) {
                    // https://github.com/bnomei/kirby3-thumb-imageoptim/issues/2
                    $pos = strpos($v, \Kirby\Cms\Dir::$numSeparator); // '_'
                    if ($pos === false) {
                        $v;
                    } else {
                        return substr($v, $pos+1);
                    }
                }, $path);
                $pathO = implode('/', $pathO);

                $page = page($pathO);

                if ($img = $page->image(\pathinfo($src, PATHINFO_BASENAME))) {
                    $url = $img->url();
                    $request = $api->imageFromURL($url);
                    static::log('imageFromURL', 'debug', [
                        'src' => $src,
                        'dst' => $dst,
                        'dst-url' => $url,
                        'options' => $options,
                    ]);
                } else {
                    static::log('Image not found at Page-object', 'warning', [
                        'src' => $src,
                        'dst' => $dst,
                        'pathO' => $pathO,
                        'file' => \pathinfo($src, PATHINFO_BASENAME),
                        'options' => $options,
                    ]);
                }

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

                $bytes = null;
                // https://github.com/bnomei/kirby3-thumb-imageoptim/issues/4
                if (static::is_localhost()) {
                    $bytes = $request->getBytes();
                } else {
                    // https://github.com/ImageOptim/php-imageoptim-api#apiurl--debug-or-use-another-https-client
                    $bytes = \Kirby\Http\Remote::get($request->apiURL(), ['method' => 'POST'])->content();
                }

                static::log('Image URL', 'info', [
                    'url' => $request->apiURL()
                ]);

                $success = $bytes ? \Kirby\Toolkit\F::write($dst, $bytes) : false;
            }
        } catch (Exception $ex) {
            static::log($ex->getMessage(), 'error', [
                'src' => $src,
                'dst' => $dst,
                'options' => $options,
            ]);
            new \Kirby\Exception($ex->getMessage());
        }

        if ($success) {
            return $dst;
        }
        // else
        static::log('kirbyThumb:late', 'debug', [
            'src' => $src,
            'dst' => $dst,
            'options' => $options,
        ]);
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
