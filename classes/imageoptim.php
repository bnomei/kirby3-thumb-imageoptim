<?php

namespace Bnomei;

class Imageoptim {

  static $instance = null;
  static public function instance() {
      $apikey = option('bnomei.thumbimageoptim.apikey');
      if($apikey && !static::$instance) {
          static::$instance = new \ImageOptim\API($apikey);
      }
      return static::$instance;
  }

  static public function kirbyThumb($src, $dst, $options) {
    // https://github.com/k-next/kirby/blob/ba35c9b087156074eb79cfbc1196797ddf201702/config/components.php#L87
    $config   = kirby()->option('thumbs', []);
    $darkroom = Kirby\Image\Darkroom::factory($config['driver'] ?? 'gd', $config);
    $options  = $darkroom->preprocess($src, $options);
    $root     = (new Kirby\Cms\Filename($src, $dst, $options))->toString();
    // check if the thumbnail has to be regenerated
    if (file_exists($root) !== true || filemtime($root) < filemtime($src)) {
        Kirby\Toolkit\F::copy($src, $root);
        $darkroom->process($root, $options);
    }
    return $root;
  }

  static public function thumb($src, $dst, $options) {
    $api = static::instance();
    if(!option('bnomei.thumbimageoptim.optimize') || !$api) return static::kirbyThumb($src, $dst, $options);

    $success = false;
    $defaults = option('bnomei.thumbimageoptim.defaults');
    $settings = array_merge($options, $defaults);

    try {
        // https://github.com/ImageOptim/php-imageoptim-api
        $request = null;
        if(static::is_localhost()) {
            // upload
            $request = $api->imageFromPath($src);
        } else {
            // request download

            // TODO: splitting path is a hack. might not be underscore forever.
            $path = explode('/', ltrim(str_replace(kirby()->roots()->content(), '', \dirname($src)), '/'));
            $pathO = array_map(function($v) {
                $pos = strpos($v, '_');
                if($pos === false) {
                    $v;
                } else {
                    return substr($v, $pos+1);
                }
            }, $path);
            $pathO = implode('/', $pathO);
            $page = page($pathO);
            
            if($img = $page->files(\pathinfo($src, PATHINFO_BASENAME))->first()) {
                $url = $img->url();
            }
            $request = $api->imageFromURL($url);
        }
        if($request) {
            $request = $request->resize(
                $settings['width'],
                $settings['height'],
                $settings['crop'] == 1 ? 'crop' : 'scale-down'
            )->quality(
                $settings['io_quality']
            )
            ->dpr(intval($settings['io_dpr']));

            if($tl = option('bnomei.thumbimageoptim.timelimit')) {
                set_time_limit(intval($tl));
            }
            $success = \Kirby\Toolkit\F::write($dst, $request->getBytes());
        }
    } catch(Exception $ex) {
        new \Kirby\Exception($ex->getMessage());
    }

    if($success) {
        return $dst;
    }
    return static::kirbyThumb($src, $dst, $options);
  }

  static private function is_localhost() {
    $whitelist = array( '127.0.0.1', '::1' );
    if( in_array( $_SERVER['REMOTE_ADDR'], $whitelist) )
        return true;
  }
}
