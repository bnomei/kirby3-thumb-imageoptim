<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\A;
use function option;

final class Imageoptim
{
    /*
     * @var array
     */
    private $options;

    /**
     * Imageoptim constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
        ];
        $this->options = array_merge($defaults, $options);
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
     * @param $src
     * @param $dst
     * @param $thumbOptions
     * @return mixed
     */
    public function optimize($src, $dst, $thumbOptions)
    {
        $this->push($dst, date('c'));

        $thumbOptions = array_merge($this->options, $thumbOptions);

        $thumb = new Thumb($thumbOptions);
        $filePath = $thumb->imageoptim($src, $dst, $thumbOptions);
        if (is_null($filePath)) {
            $filePath = $thumb->core($src, $dst, $thumbOptions);
        }

        $this->pop($dst);
        return $dst;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     * @throws InvalidArgumentException
     */
    public function push(string $key, string $value): bool
    {
        $id = strval(crc32($key));
        kirby()->cache('bnomei.thumbimageoptim.stack')->set(
            $id,
            [
                'dst' => $key,
                'time' => $value,
            ]
        );
        $index = kirby()->cache('bnomei.thumbimageoptim.index')->get('index', []);
        $index[] = $id;
        return kirby()->cache('bnomei.thumbimageoptim.index')->set('index', $index);
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function pop(string $key): bool
    {
        $id = strval(crc32($key));
        kirby()->cache('bnomei.thumbimageoptim.stack')->remove(
            $id
        );
        $index = kirby()->cache('bnomei.thumbimageoptim.index')->get('index', []);
        $found = array_search($id, $index);
        if ($found !== false) {
            unset($index[$found]);
        }
        return kirby()->cache('bnomei.thumbimageoptim.index')->set('index', $index);
    }

    /**
     * Thumb copies original file before optimizing.
     * Remove these if api call did not finish.
     */
    public function removeAllUnoptimized()
    {
        $index = kirby()->cache('bnomei.thumbimageoptim.index')->get('index', []);
        foreach ($index as $id) {
            $dst = A::get(
                kirby()->cache('bnomei.thumbimageoptim.stack')->get($id, []),
                'dst'
            );
            $this->removeDst($dst);
        }
    }

    /**
     * @param string|null $dst
     * @return bool
     */
    public function removeDst(?string $dst = null): bool
    {
        if (!$dst) {
            return false;
        }
        if (file_exists($dst) && !unlink($dst)) {
            return false;
        }
        return $this->pop($dst);
    }

    /*
     * @var Imageoptim
     */
    private static $singleton = null;

    /**
     * @param array $options
     * @return Imageoptim
     * @codeCoverageIgnore
     */
    public static function singleton(array $options = []): Imageoptim
    {
        if (self::$singleton) {
            return self::$singleton;
        }

        self::$singleton = new self($options);
        return self::$singleton;
    }
}
