<?php

declare(strict_types=1);

namespace OpenAPI\Filter;

use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\PathMatcher as PathMatcherHelper;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Filter\PathMatcher
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 * @uses   \Membrane\OpenAPI\PathMatcher
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class PathMatcherTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'convert url to a field set of path parameters';
        $sut = new PathMatcher(self::createStub(PathMatcherHelper::class));

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $pathMatcherHelper = new PathMatcherHelper('/api', '/pets');
        $sut = new PathMatcher($pathMatcherHelper);


        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /** @test */
    public function invalidResultForNonStringValues(): void
    {
        $expected = Result::invalid(
            false,
            new MessageSet(null, new Message('PathMatcher filter expects string, %s passed', ['boolean']))
        );
        $sut = new PathMatcher(self::createStub(PathMatcherHelper::class));

        $actual = $sut->filter(false);

        self::assertEquals($expected, $actual);
    }

    /** @test */
    public function invalidResultForMismatchedPath(): void
    {
        $expected = Result::invalid(
            '/hats/23',
            new MessageSet(null, new Message('requestPath does not match expected pattern', []))
        );
        $apiPath = '/pets/{id}';
        $requestPath = '/hats/23';
        $sut = new PathMatcher(new PathMatcherHelper('https://www.server.com', $apiPath));

        $actual = $sut->filter($requestPath);

        self::assertEquals($expected, $actual);
    }

    /** @test */
    public function filterTest(): void
    {
        $expected = Result::noResult(['filtered value']);
        $observer = self::createMock(PathMatcherHelper::class);
        $observer->expects($this->once())
            ->method('getPathParams')
            ->with('value')
            ->willReturn(['filtered value']);
        $sut = new PathMatcher($observer);

        $actual = $sut->filter('value');

        self::assertEquals($expected, $actual);
    }
}
