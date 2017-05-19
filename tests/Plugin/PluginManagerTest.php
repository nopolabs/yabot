<?php

namespace Plugin;


use Nopolabs\Yabot\Plugin\PluginManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PluginManagerTest extends TestCase
{
    public function matchesPrefixDataProvider()
    {
        return [
            ['<none>', 'test text', ['test text', 'test text']],
            ['test', 'test text', ['test text', 'text']],
            ['test', 'no-match text', []],
        ];
    }

    /**
     * @dataProvider matchesPrefixDataProvider
     */
    public function testMatchesPrefix($prefix, $text, $expected)
    {
        $manager = new PluginManager($this->createMock(LoggerInterface::class));

        $matches = $manager->matchesPrefix($prefix, $text);

        $this-> assertEquals($expected, $matches);
    }
}