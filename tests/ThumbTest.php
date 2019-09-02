<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Thumb;
use Kirby\Toolkit\Dir;
use PHPUnit\Framework\TestCase;

final class ThumbTest extends TestCase
{
    private $src;
    private $dst;
    private $opt;

    public function setUp(): void
    {
        Dir::remove(__DIR__. '/media/pages/home');
        $this->src = __DIR__ . '/content/home/flowers.jpg';
        $this->dst = __DIR__ . '/media/pages/home/flowers-optimized.jpg';
        $this->opt = [
            'width' => 844,
        ];
    }

    public function testContructs()
    {
        $thumb = new Thumb();
        $this->assertInstanceOf(Thumb::class, $thumb);
    }

    public function testOptions()
    {
        $thumb = new Thumb(['debug' => true]);
        $this->assertCount(7, $thumb->option());
        $this->assertTrue($thumb->option('debug'));
    }

    public function testCore()
    {
        $thumb = new Thumb();
        $thumb->core($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
        // BIGGER than half
        $this->assertTrue(F::size($this->dst) >= F::size($this->src) * 0.5);
    }

    public function testOptimize()
    {
        $thumb = new Thumb([
            'forceuploads' => true,
        ]);
        $thumb->imageoptim($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
        // SMALLER than half
        $this->assertTrue(F::size($this->dst) <= F::size($this->src) * 0.5);
    }

    public function testNoApiKey()
    {
        $thumb = new Thumb([
            'apikey' => '',
        ]);
        $this->assertNull($thumb->imageoptim($this->src, $this->dst, $this->opt));
    }

    public function testInvalidApiKey()
    {
        $thumb = new Thumb([
            'apikey' => 'Notanvalidapikey',
        ]);
        $this->assertFalse($thumb->imageoptim($this->src, $this->dst, $this->opt));
    }

    public function testRequestNoApi()
    {
        $thumb = new Thumb([
            'apikey' => '',
        ]);
        $this->assertNull($thumb->imageoptimRequest($this->src, $this->dst));
    }

    public function testInvalidFitOption()
    {
        $thumb = new Thumb([
            'forceuploads' => true,
            'apirequest' => [
                'io_quality' => 'medium',
                'io_dpr' => '1',
                'crop' => 'notvalidfit'
            ],
        ]);
        $thumb->imageoptim($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
    }

    public function testTimelimit()
    {
        $thumb = new Thumb([
            'forceuploads' => true,
            'timelimit' => 30,
        ]);
        $thumb->imageoptim($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
    }
}
