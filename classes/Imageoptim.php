<?php

namespace Bnomei;

class Imageoptim
{
    private static $instance = null;
    public static function instance()
    {
        $apikey = option('bnomei.thumbimageoptim.apikey');
        if (is_callable($apikey)) {
            $apikey = trim($apikey());
        }
        if ($apikey && !static::$instance) {
            static::$instance = new \ImageOptim\API($apikey);
        }
        return static::$instance;
    }

    private static function push(string $key, string $value)
    {
        kirby()->cache('bnomei.thumbimageoptim')->set(\md5($key), [
            'dst' => $key,
            'time' => $value,
        ]);
    }

    private static function pop(string $key)
    {
        kirby()->cache('bnomei.thumbimageoptim')->remove(\md5($key));
    }

    public static function removeFilesOfUnfinishedJobs()
    {
        $r = kirby()->roots()->cache() . '/bnomei/thumbimageoptim';
        $cachefiles = \Kirby\Toolkit\Dir::files($r);
        foreach ($cachefiles as $file) {
            $md5 = basename($file, '.cache');
            if ($job = kirby()->cache('bnomei.thumbimageoptim')->get($md5)) {
                if (is_array($job) && array_key_exists('dst', $job)) {
                    $dst = $job['dst'];
                    if (file_exists($dst)) {
                        if (unlink($dst)) {
                            static::pop($dst);
                        }
                    } else {
                        static::pop($dst);
                    }
                }
            }
        }
    }

    private static function log(string $msg = '', string $level = 'info', array $context = []): bool
    {
        $log = option('bnomei.thumbimageoptim.log');
        if ($log && is_callable($log)) {
            if (!option('debug') && $level == 'debug') {
                // skip but...
                return true;
            } else {
                return $log($msg, $level, $context);
            }
        }
        return false;
    }

    public static function kirbyThumb($src, $dst, $options)
    {
        // https://github.com/getkirby/kirby/blob/master/config/components.php#L85
        $darkroom = \Kirby\Image\Darkroom::factory(option('thumbs.driver', 'gd'), option('thumbs', []));
        $options  = $darkroom->preprocess($src, $options);
        $root     = (new \Kirby\Cms\Filename($src, $dst, $options))->toString();
        \Kirby\Toolkit\F::copy($src, $root);
        $darkroom->process($root, $options);
        return $root;
    }

    public static function thumb($src, $dst, $options)
    {
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

        static::push($dst, date('c'));

        try {
            // https://github.com/ImageOptim/php-imageoptim-api
            $request = null;
            if (static::is_localhost() || option('bnomei.thumbimageoptim.forceupload')) {
                // upload
                $request = $api->imageFromPath($src);
                static::log('imageFromPath', 'debug', [
                    'src' => $src,
                    'dst' => $dst,
                    'options' => $options,
                ]);
            } else {
                // request download
                $path = explode('/', ltrim(str_replace(kirby()->roots()->content(), '', \dirname($src)), '/'));
                $pathO = array_map(function ($v) {
                    // https://github.com/bnomei/kirby3-thumb-imageoptim/issues/2
                    $pos = strpos($v, \Kirby\Cms\Dir::$numSeparator); // '_'
                    if ($pos === false) {
                        return $v;
                    } else {
                        return substr($v, $pos + 1);
                    }
                }, $path);
                $pathO = implode('/', $pathO);

                $page = page($pathO);
                \Kirby\Toolkit\F::copy($src, $dst); // or url will not work

                if ($img = $page->image(\pathinfo($src, PATHINFO_BASENAME))) {
                    $url = $img->url();
                    $request = $api->imageFromURL($url);

                    if (option('bnomei.thumbimageoptim.log.enabled')) {
                        static::log('imageFromURL', 'debug', [
                            'src' => $src,
                            'dst' => $dst,
                            'dst-url' => $url,
                            'options' => $options,
                        ]);
                    }
                } else {
                    if (option('bnomei.thumbimageoptim.log.enabled')) {
                        static::log('Image not found at Page-object', 'warning', [
                            'src' => $src,
                            'dst' => $dst,
                            'pathO' => $pathO,
                            'file' => \pathinfo($src, PATHINFO_BASENAME),
                            'options' => $options,
                        ]);
                    }
                }
            }
            if ($request) {
                $fit = \Kirby\Toolkit\A::get($settings, 'crop', 'crop');
                $allowedFitOptions = ['fit', 'crop', 'scale-down', 'pad'];
                if (null !== $fit && !in_array($fit, $allowedFitOptions)) {
                    $fit = 'crop';
                }
                $request = $request->resize(
                    \Kirby\Toolkit\A::get($settings, 'width'),
                    \Kirby\Toolkit\A::get($settings, 'height'),
                    \Kirby\Toolkit\A::get($settings, 'height') === null ? null : $fit
                );
                if ($io_quality = \Kirby\Toolkit\A::get($settings, 'io_quality')) {
                    $request = $request->quality($io_quality);
                }
                if ($io_dpr = \Kirby\Toolkit\A::get($settings, 'io_dpr')) {
                    $request = $request->dpr(intval($io_dpr));
                }

                if ($tl = option('bnomei.thumbimageoptim.timelimit')) {
                    set_time_limit(intval($tl));
                }

                $bytes = null;
                // https://github.com/bnomei/kirby3-thumb-imageoptim/issues/4
                if (static::is_localhost() || option('bnomei.thumbimageoptim.forceupload')) {
                    $bytes = $request->getBytes();
                } else {
                    static::log('Image URL', 'debug', [
                        'url' => $request->apiURL()
                    ]);

                    // https://github.com/ImageOptim/php-imageoptim-api#apiurl--debug-or-use-another-https-client
                    $bytes = \Kirby\Http\Remote::get($request->apiURL(), ['method' => 'POST'])->content();
                }

                $success = $bytes ? \Kirby\Toolkit\F::write($dst, $bytes) : false;
                if ($success) {
                    static::pop($dst);
                }
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
        return null;
    }

    private static function is_localhost()
    {
        $whitelist = array('127.0.0.1', '::1');
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return true;
        }
    }
}
