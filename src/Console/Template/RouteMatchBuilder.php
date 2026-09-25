<?php

declare(strict_types=1);

namespace Membrane\Console\Template;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinition;

class RouteMatchBuilder implements PHPClassDefinition
{
    private const string TEMPLATE_CODE = <<<'END'
<?php

declare(strict_types=1);

namespace %s;

use Membrane\Builder\{Builder, Specification};
use Membrane\OpenAPI\Specification\RouteMatch;
use Membrane\Processor;

class %s implements Builder
{
    private const string OPEN_API_FILENAME = '%s';
    private const array MAP = [%s];

    /**
     * @phpstan-assert-if-true RouteMatch $specification
    */
    public function supports(Specification $specification): bool
    {
        return $specification instanceof RouteMatch
            && realpath($specification->source) === self::OPEN_API_FILENAME
            && isset(self::MAP[$specification->operationId]);
    }

    public function build(Specification $specification): Processor
    {
        assert($this->supports($specification));

        return new (self::MAP[$specification->operationId])();
    }
}
END;

    private readonly string $openAPIFilePath;

    /** @param array<string, string> $map */
    public function __construct(
        private readonly string $namespace,
        private readonly string $className,
        string $openAPIFilePath,
        private readonly array $map
    ) {
        $this->openAPIFilePath = realpath($openAPIFilePath) ?: $openAPIFilePath;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getName(): string
    {
        return 'CachedRouteMatchBuilder';
    }

    public function getCode(): string
    {
        $implodedMap = '';
        foreach ($this->map as $operationId => $processor) {
            $implodedMap .= sprintf('\'%s\' => \'%s\', ', $operationId, $processor);
        }

        return sprintf(
            self::TEMPLATE_CODE,
            $this->namespace,
            $this->className,
            $this->openAPIFilePath,
            $implodedMap,
        );
    }
}
