<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/thumbimageoptim', [
    'options' => [
      'optimize' => true,
      'forceupload' => false,
      'apikey' => null,
      'defaults' => [
          'io_quality' => 'medium',
          'io_dpr' => '1',
      ],
      'timelimit' => 30, // set_time_limit
      'log.enabled' => false,
      'log' => function (string $msg, string $level = 'info', array $context = []):bool {
          if (option('bnomei.thumbimageoptim.log.enabled') && function_exists('kirbyLog')) {
              kirbyLog('bnomei.thumbimageoptim.log')->log($msg, $level, $context);
              return true;
          }
          return false;
      },
      'cache' => true,
    ],
    'components' => [
        'thumb' => function ($kirby, $src, $dst, $options) {
            return \Bnomei\Imageoptim::thumb($src, $dst, $options);
        }
    ],
    'hooks' => [
      'route:before' => function () {
          \Bnomei\Imageoptim::removeFilesOfUnfinishedJobs();
      },
    ],
  ]);
