<?php
namespace Nopolabs\Yabot\Tests\Helpers;

use Nopolabs\Yabot\Helpers\ConfigTrait;
use PHPUnit\Framework\TestCase;

class ConfigTraitTest extends TestCase
{
    use ConfigTrait;

    public function testTrait()
    {
        $this->assertEquals([], $this->getConfig());
        $this->assertFalse($this->has('key'));
        $this->assertNull($this->get('key'));
        $this->assertEquals('default', $this->get('key', 'default'));

        $this->setConfig(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $this->getConfig());
        $this->assertTrue($this->has('key'));
        $this->assertEquals('value', $this->get('key'));
        $this->assertEquals('value', $this->get('key', 'default'));

        $this->assertFalse($this->has('not-key'));
        $this->assertNull($this->get('not-key'));
        $this->assertEquals('default', $this->get('not-key', 'default'));
    }
}