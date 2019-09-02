<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/thumbimageoptim', [
    'options' => [
        'apikey' => function () {
            return null;
        // return env('IMAGEOPTIM_APIKEY');
        },
        'enabled' => true,
        'forceupload' => false,
        'timelimit' => null, // use server default
        'apirequest' => [
            'io_quality' => 'medium',
            'io_dpr' => '1',
        ],
        'cache.stack' => true,
        'cache.index' => true,
    ],
    'components' => [
        'thumb' => function ($kirby, $src, $dst, $options) {
            return \Bnomei\Imageoptim::singleton()->optimize($src, $dst, $options);
        },
    ],
    'fileMethods' => [
        'thumbimageoptim' => function (?int $w = null, ?int $h = null, ?int $q = null) {
            // NOTE: $this->resize() worked in k 3.1.x but not anymore
            $w = $w ?? $this->width();
            $h = $h ?? $this->height();
            $q = $q ?? option('thumbs.quality', 80);
            // NOTE: mapping int to io_string of quality is useless. use only global io setting.
            return $this->resize($w, $h); // NOT using: $q
        },
    ],
    'hooks' => [
        'route:before' => function () {
            \Bnomei\Imageoptim::singleton()->removeAllUnoptimized();
        },
    ],
]);
