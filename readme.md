# Kirby 3 Thumb Imageoptim

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-thumb-imageoptim?color=ae81ff)
![Stars](https://flat.badgen.net/packagist/ghs/bnomei/kirby3-thumb-imageoptim?color=272822)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-thumb-imageoptim?color=272822)
![Issues](https://flat.badgen.net/packagist/ghi/bnomei/kirby3-thumb-imageoptim?color=e6db74)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-thumb-imageoptim)](https://travis-ci.com/bnomei/kirby3-thumb-imageoptim)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-thumb-imageoptim)](https://coveralls.io/github/bnomei/kirby3-thumb-imageoptim) 
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-thumb-imageoptim)](https://codeclimate.com/github/bnomei/kirby3-thumb-imageoptim) 
[![Demo](https://flat.badgen.net/badge/website/examples?color=f92672)](https://kirby3-plugins.bnomei.com/thumb-imageoptim) 
[![Gitter](https://flat.badgen.net/badge/gitter/chat?color=982ab3)](https://gitter.im/bnomei-kirby-3-plugins/community) 
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)



Kirby 3 CMS Thumb Component to optimize images using ImageOptim Api.

## Commerical Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/3) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-thumb-imageoptim/archive/master.zip) as folder `site/plugins/kirby3-thumb-imageoptim` or
- `git submodule add https://github.com/bnomei/kirby3-thumb-imageoptim.git site/plugins/kirby3-thumb-imageoptim` or
- `composer require bnomei/kirby3-thumb-imageoptim`

## Requirements

- [ImageOptim API key](https://imageoptim.com/api/register) (trial available). This plugin uses v1.3.1.

## Setup

In your `site/config.php` options set the [ImageOptim API key](https://imageoptim.com/api/register).

```php
'options' => [
    'bnomei.thumbimageoptim.apikey' => 'YOUR_API_KEY_HERE',
    // ... other options
]
```

Generate thumbs as usual using `resize()` File Method.

```php
$img = $page->image('ukulele.jpg');
echo $img->resize(234)->html();
```

> *TIP:*
> If you want your image to be optimized but retain their original size use:
> `$img->resize()` without providing a width or height.

## Settings

| bnomei.thumbimageoptim.   | Default        | Description               |            
|---------------------------|----------------|---------------------------|
| apikey | `null` | Your imageoptim apikey as string. |
| optimize | `true` | set to `false` to disable optimization with this plugin |
| forceupload | `false` | set to `true` when images are not public available (like a website with htpasswd). |

> TIP: You can also set a callback if you use the [dotenv Plugin](https://github.com/bnomei/kirby3-dotenv). <br>`'bnomei.thumbimageoptim.apikey' => function() { return env('IMAGEOPTIM_APIKEY'); },`

> DANGER: Content is always uploaded on localhost. `allow_url_fopen` PHP setting must be enabled for the API to do uploading. Check with `ini_get('allow_url_fopen')`. Please be aware of the potential security risks caused by `allow_url_fopen`!

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-thumb-imageoptim/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
