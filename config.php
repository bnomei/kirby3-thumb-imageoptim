<?php

Bnomei\Imageoptim::beforeRegisterComponent();

Kirby::plugin('bnomei/thumbimageoptim', [
  'options' => [
    'optimize' => true,
    'apikey' => null,
    'defaults' => [
        'io_quality' => 'medium',
        'io_dpr' => '1',
    ],
    'timelimit' => 30, // set_time_limit
  ],
  'components' => [
      'thumb' => function ($kirby, $src, $dst, $options) {
          return \Bnomei\Imageoptim::thumb($src, $dst, $options);
      }
    ]
]);
