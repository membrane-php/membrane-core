<?php

declare(strict_types=1);

namespace Membrane\Tests;

use Membrane\Processor;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

abstract class MembraneTestCase extends TestCase
{
    final protected static function assertProcessorEquals(
        Processor $expected,
        Processor $actual,
    ): void {
        self::assertEquals($expected, $actual, sprintf(
            <<<TEXT
            expected: 
            %s
            actual: 
            %s
            
            TEXT,
            $expected,
            $actual,
        ));
    }

    final protected static function assertResultEquals(
        Result $expected,
        Result $actual,
    ): void {
        $message = sprintf(
            <<<TEXT
            expected: 
            %s
            actual: 
            %s
            
            TEXT,
            (new HumanReadable($expected))->toString(),
            (new HumanReadable($actual))->toString(),
        );

        self::assertEquals($expected, $actual, $message);
    }
}
