# Kirby 3 Thumb Imageoptim

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-thumb-imageoptim.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

Kirby 3 CMS Thumb Component to optimize images using ImageOptim Api.

This plugin is free but if you use it in a commercial project please consider to [make a donation 🍻](https://www.paypal.me/bnomei/3).


## Requirements

- [ImageOptim API key](https://imageoptim.com/api/register) (trial available). This plugin uses v1.3.1.
- `allow_url_fopen` PHP setting must be enabled for the API to work. Check with `ini_get('allow_url_fopen')`. Please be aware of the potential security risks caused by allow_url_fopen!

## Setup

In your `site/config.php` options set the [ImageOptim API key](https://imageoptim.com/api/register).

```php
'options' => [
    'bnomei.thumbimageoptim.apikey' => 'YOUR_API_KEY_HERE',
    // ... other options
]
```

## Usage

Generate thumbs as usual using `resize()` File Method.

```php
$img = $page->image('ukulele.jpg');
echo $img->resize(234)->html();
```

> *TIP:*
> If you want your image to be optimized but retain original size use:
> `$img->resize()` without providing a width or height.

## Settings

### bnomei.thumbimageoptim.optimize
- default: `true`
- set to `false` to disable optimization with this plugin

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-thumb-imageoptim/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
