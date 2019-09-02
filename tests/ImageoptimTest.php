<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Imageoptim;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\F;
use PHPUnit\Framework\TestCase;

final class ImageoptimTest extends TestCase
{
    private $src;
    private $dst;
    private $opt;

    public function setUp(): void
    {
        Dir::remove(__DIR__ . '/media/pages/home');
        $this->src = __DIR__ . '/content/home/flowers.jpg';
        $this->dst = __DIR__ . '/media/pages/home/flowers-optimized.jpg';
        $this->opt = [
            'width' => 844,
        ];
    }

    public function testContructs()
    {
        $iothu = new Imageoptim();
        $this->assertInstanceOf(Imageoptim::class, $iothu);
    }

    public function testOptions()
    {
        $iothu = new Imageoptim(['debug' => true]);
        $this->assertCount(1, $iothu->option());
        $this->assertTrue($iothu->option('debug'));
    }

    public function testOptimize()
    {
        $iothu = new Imageoptim([
            'forceuploads' => true,
        ]);
        $iothu->optimize($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
        // SMALLER than half
        $this->assertTrue(F::size($this->dst) <= F::size($this->src) * 0.5);
    }

    public function testWillNotOptimize()
    {
        $iothu = new Imageoptim([
            'apikey' => null,
        ]);
        $iothu->optimize($this->src, $this->dst, $this->opt);
        $this->assertTrue(F::exists($this->dst));
        // BIGGER than half
        $this->assertTrue(F::size($this->dst) >= F::size($this->src) * 0.5);
    }

    public function testStaticSingleton()
    {
        $iothu = \Bnomei\Imageoptim::singleton();
        $this->assertInstanceOf(Imageoptim::class, $iothu);

        // again for singleton check
        $iothu = \Bnomei\Imageoptim::singleton();
        $this->assertInstanceOf(Imageoptim::class, $iothu);
    }

    public function testRemoveAllUnoptimized()
    {
        $iothu = new Imageoptim();

        F::copy($this->src, $this->dst); // original copied but not optimized
        $id = strval(crc32($this->dst));
        kirby()->cache('bnomei.thumbimageoptim.index')->set('index', [
            $id, // ... there could be more
        ]);
        kirby()->cache('bnomei.thumbimageoptim.stack')->set($id, [
            'dst' => $this->dst,
            'time' => date('c'),
        ]);

        $iothu->removeAllUnoptimized();

        $index = kirby()->cache('bnomei.thumbimageoptim.index')->get('index', []);
        $this->assertCount(0, $index);
        $job = kirby()->cache('bnomei.thumbimageoptim.index')->get($id);
        $this->assertNull($job);
    }

    public function testRemoveDst()
    {
        $iothu = new Imageoptim();
        $this->assertFalse($iothu->removeDst());

        F::copy($this->src, $this->dst);
        $this->assertTrue($iothu->removeDst($this->dst));
    }
}
