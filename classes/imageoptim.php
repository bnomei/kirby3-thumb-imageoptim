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
      // TODO: call kirby thumbs() with options
      \Kirby\Toolkit\F::copy($src, $dst);
      return $dst;
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
            $url = str_replace(kirby()->roots()->base(), kirby()->site()->url(), $src);
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
