<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher as PathMatcherHelper;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathMatcher::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[UsesClass(PathMatcherHelper::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class PathMatcherTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert url to a field set of path parameters';
        $sut = new PathMatcher(self::createStub(PathMatcherHelper::class));

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $pathMatcherHelper = new PathMatcherHelper('/api', '/pets');
        $sut = new PathMatcher($pathMatcherHelper);


        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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
