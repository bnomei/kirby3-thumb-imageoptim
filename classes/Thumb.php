<?php

declare(strict_types=1);

namespace Bnomei;

use ImageOptim\API;
use ImageOptim\FileRequest;
use ImageOptim\URLRequest;
use Kirby\Cms\Dir;
use Kirby\Cms\File;
use Kirby\Cms\Filename;
use Kirby\Http\Remote;
use Kirby\Image\Darkroom;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;
use function option;

final class Thumb
{
    /*
     * @var API
     */
    private $api;

    /**
     * Thumb constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'enabled' => option('bnomei.thumbimageoptim.enabled'),
            'forceupload' => option('bnomei.thumbimageoptim.forceupload'),
            'isLocal' => kirby()->system()->isLocal(),
            'apikey' => option('bnomei.thumbimageoptim.apikey'),
            'apirequest' => option('bnomei.thumbimageoptim.apirequest'),
            'timelimit' => option('bnomei.thumbimageoptim.timelimit'),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if (is_callable($call) && in_array($key, ['apikey', 'enabled'])) {
                $this->options[$key] = $call();
            }
        }

        $apikey = trim(A::get($this->options, 'apikey', ''));
        if (strlen($apikey)) {
            $this->api = new API($apikey);
        }
    }

    /**
     * @param string|null $key
     * @return array|mixed
     */
    public function option(?string $key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }

    /**
     * @param string $src
     * @param string $dst
     * @param array $options
     * @return string
     */
    public function core(string $src, string $dst, array $options = [])
    {
        // https://github.com/getkirby/kirby/blob/master/config/components.php#L85
        $darkroom = Darkroom::factory(
            option('thumbs.driver', 'gd'),
            option('thumbs', [])
        );
        $options = $darkroom->preprocess($src, $options);
        $root = (new Filename($src, $dst, $options))->toString();
        F::copy($src, $root);
        $darkroom->process($root, $options);
        return $root;
    }

    /**
     * @param string $src
     * @param string $dst
     * @param array $thumbOptions
     * @return bool|null
     */
    public function imageoptim(string $src, string $dst, array $thumbOptions = [])
    {
        if (!$this->api || !$this->option('enabled')) {
            return null;
        }

        $request = $this->imageoptimRequest($src, $dst);
        if (!$request) {
            return null;
        }

        $defaults = $this->option('apirequest');
        $thumbOptions = array_merge($defaults, $thumbOptions);
        $request = $this->imageoptimApplyOptions($request, $thumbOptions);
        $bytes = $this->imageoptimBytes($request);

        return $bytes ? F::write($dst, $bytes) : false;
    }

    /**
     * @param string $src
     * @param string $dst
     * @return FileRequest|URLRequest|null
     */
    public function imageoptimRequest(string $src, string $dst)
    {
        if (!$this->api) {
            return null;
        }

        // upload
        if ($this->option('isLocal') || $this->option('forceupload')) {
            return $this->api->imageFromPath($src);
        }

        // request download
        F::copy($src, $dst); // IMPORTANT: forced copy the original or url will not work
        $img = $this->imageFromSrc($src);
        if ($img) {
            return $this->api->imageFromURL($img->url());
        }

        return null;
    }

    /**
     * @param string $src
     * @return File|null
     */
    public function imageFromSrc(string $src): ?File
    {
        $path = explode('/', ltrim(str_replace(kirby()->roots()->content(), '', dirname($src)), '/'));
        $pathO = array_map(function ($value) {
            // https://github.com/bnomei/kirby3-thumb-imageoptim/issues/2
            $pos = strpos($value, Dir::$numSeparator); // '_'
            if ($pos === false) {
                return $value;
            }
            return substr($value, $pos + 1);
        }, $path);
        $pathO = implode('/', $pathO);
        $page = page($pathO);
        return $page->image(pathinfo($src, PATHINFO_BASENAME));
    }

    /**
     * @param $request
     * @param array $options
     * @return mixed
     */
    public function imageoptimApplyOptions($request, array $options = [])
    {
        $fit = A::get($options, 'crop', 'crop');
        $allowedFitOptions = ['fit', 'crop', 'scale-down', 'pad'];
        if ($fit !== null && !in_array($fit, $allowedFitOptions)) {
            $fit = 'crop';
        }
        $request = $request->resize(
            A::get($options, 'width'),
            A::get($options, 'height'),
            A::get($options, 'height') === null ? null : $fit
        );

        $io_quality = A::get($options, 'io_quality');
        if ($io_quality) {
            $request = $request->quality($io_quality);
        }

        $io_dpr = A::get($options, 'io_dpr');
        if ($io_dpr) {
            $request = $request->dpr(intval($io_dpr));
        }

        $timelimit = $this->option('timelimit');
        if ($timelimit) {
            $request = $request->timeout(intval($timelimit));
        }

        return $request;
    }

    /**
     * @param $request
     * @return mixed|null
     */
    public function imageoptimBytes($request)
    {
        $timelimit = $this->option('timelimit');
        if ($timelimit) {
            // one second more than request
            set_time_limit(intval($timelimit) + 1);
        }
        if (is_a($request, FileRequest::class)) {
            try {
                return $request->getBytes();
            } catch (\Exception $exception) {
                return null;
            }
        } elseif (is_a($request, URLRequest::class)) {
            // https://github.com/ImageOptim/php-imageoptim-api#apiurl--debug-or-use-another-https-client
            return Remote::get(
                $request->apiURL(),
                ['method' => 'POST']
            )->content();
        }
        return null;
    }
}
