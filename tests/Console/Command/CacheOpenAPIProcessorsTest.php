<?php

declare(strict_types=1);

namespace Console\Command;


use Membrane\Console\Command\CacheOpenAPIProcessors;
use Membrane\OpenAPI\Builder as Builder;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use Membrane\OpenAPI\Specification as Specification;
use Membrane\Processor;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CacheOpenAPIProcessors::class)]
#[CoversClass(CannotReadOpenAPI::class)]
#[UsesClass(OpenAPIFileReader::class)]
#[UsesClass(Builder\APIBuilder::class)]
#[UsesClass(Builder\Arrays::class)]
#[UsesClass(Builder\Numeric::class)]
#[UsesClass(Builder\Operation::class)]
#[UsesClass(Builder\Strings::class)]
#[UsesClass(Specification\APISchema::class)]
#[UsesClass(Specification\Arrays::class)]
#[UsesClass(Specification\Numeric::class)]
#[UsesClass(Specification\OpenAPI::class)]
#[UsesClass(Specification\Operation::class)]
#[UsesClass(Specification\Path::class)]
#[UsesClass(Specification\Strings::class)]
#[UsesClass(Processor\BeforeSet::class)]
#[UsesClass(Processor\Collection::class)]
#[UsesClass(Processor\Field::class)]
#[UsesClass(Processor\FieldSet::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsString::class)]
class CacheOpenAPIProcessorsTest extends TestCase
{
    private vfsStreamDirectory $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup('cache');
    }

    #[Test]
    #[TestDox('It will output an error message if it lacks write permission to the destination filepath')]
    public function outputsErrorForReadonlyFilePaths(): void
    {
        $correctApiPath = __DIR__ . '/../../fixtures/docs/petstore-expanded.json';
        chmod(vfsStream::url('cache'), 0444);
        $readonlyDestination = vfsStream::url('cache');
        $sut = new CommandTester(new CacheOpenAPIProcessors());

        $sut->execute(['openAPI' => $correctApiPath, 'destination' => $readonlyDestination]);

        self::assertSame(Command::FAILURE, $sut->getStatusCode());

        self::assertSame(
            sprintf('%s cannot be written to', vfsStream::url('cache')),
            trim($sut->getDisplay(true))
        );
    }

    public static function provideCasesThatWillFailToRead(): array
    {
        return [
            'cannot read from relative filename' => [
                '/../../fixtures/docs/petstore-expanded.json',
                vfsStream::url('cache') . '/routes.php',
                Command::FAILURE,
            ],
            'cannot route from an api with no routes' => [
                __DIR__ . '/../../fixtures/simple.json',
                vfsStream::url('cache') . '/routes.php',
                Command::FAILURE,
            ],
        ];
    }

    #[Test]
    #[TestDox('It will output an error message if there is a failure attempting to read from the OpenAPI filepath')]
    #[DataProvider('provideCasesThatWillFailToRead')]
    public function outputsErrorForFailureToReadOpenAPI(
        string $openAPI,
        string $destination,
        int $expectedStatusCode
    ): void {
        $sut = new CommandTester(new CacheOpenAPIProcessors());

        $sut->execute(['openAPI' => $openAPI, 'destination' => $destination]);

        self::assertSame($expectedStatusCode, $sut->getStatusCode());
    }


    public static function provideExpectedProcessorsForPetStoreExpanded(): array
    {
        return [
            'findPets' => [
                'findPets.php',
                new \Membrane\Processor\FieldSet(
                    'findPets',
                    new \Membrane\Processor\FieldSet(
                        'query',
                        new \Membrane\Processor\Collection(
                            'tags',
                            new \Membrane\Processor\BeforeSet(new \Membrane\Validator\Type\IsList()),
                            new \Membrane\Processor\Field('', new \Membrane\Validator\Type\IsString())
                        ),
                        new \Membrane\Processor\Field('limit', new \Membrane\Validator\Type\IsInt())
                    )
                ),
            ],
            'find pet by id' => [
                'find pet by id.php',
                new \Membrane\Processor\FieldSet(
                    'find pet by id',
                    new \Membrane\Processor\FieldSet(
                        'path',
                        new \Membrane\Processor\BeforeSet(new \Membrane\Validator\FieldSet\RequiredFields('id')),
                        new \Membrane\Processor\Field('id', new \Membrane\Validator\Type\IsInt())
                    )
                ),
            ],
            'addPet' => [
                'addPet.php',
                new \Membrane\Processor\FieldSet('addPet'),
            ],
            'deletePet' => [
                'deletePet.php',
                new \Membrane\Processor\FieldSet(
                    'deletePet',
                    new \Membrane\Processor\FieldSet(
                        'path',
                        new \Membrane\Processor\BeforeSet(new \Membrane\Validator\FieldSet\RequiredFields('id')),
                        new \Membrane\Processor\Field('id', new \Membrane\Validator\Type\IsInt())
                    )
                ),
            ],
        ];
    }

    #[Test]
    #[TestDox('It can successfully cache the OpenAPI petstore-expanded example')]
    #[DataProvider('provideExpectedProcessorsForPetStoreExpanded')]
    public function successfullyCachePetStoreExpandedProcessors(string $cachedFile, Processor $expectedProcessor): void
    {
        $actualProcessor = sprintf('%s/cache/%s', $this->root->url(), $cachedFile);
        $sut = new CommandTester(new CacheOpenAPIProcessors());

        $sut->execute([
            'openAPI' => __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            'destination' => $this->root->url() . '/cache/',
        ]);

        self::assertEquals($expectedProcessor, eval('?>' . file_get_contents($actualProcessor)));
    }
}
