<?php

declare(strict_types=1);

namespace Membrane\Console\Command;

use Membrane\OpenAPI\Builder\Operation;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use Membrane\OpenAPI\Specification\OpenAPI;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'membrane:membrane:generate-processors',
    description: 'Parses OpenAPI file to write a cached set of processors for each operationId',
)]
class CacheOpenAPIProcessors extends Command
{
    protected function configure(): void
    {
        self::addArgument(
            'openAPI',
            InputArgument::REQUIRED,
            'The absolute filepath to your OpenAPI'
        );
        self::addArgument(
            'destination',
            InputArgument::OPTIONAL,
            'The directory for the generated processor collection',
            __DIR__ . '/../../../cache/processors/'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = new FormatterHelper();
        $openAPIFilePath = $input->getArgument('openAPI');
        assert(is_string($openAPIFilePath));
        $existingFilePath = $destination = $input->getArgument('destination');
        assert(is_string($existingFilePath) && is_string($destination));

        while (!file_exists($existingFilePath)) {
            $existingFilePath = dirname($existingFilePath);
        }
        if (!is_writable($existingFilePath)) {
            $this->outputErrorBlock(sprintf('%s cannot be written to', $existingFilePath), $output);
            return Command::FAILURE;
        }

        try {
            $output->writeln(sprintf('Reading OpenAPI from %s', $openAPIFilePath));
            $openAPI = new OpenAPI((new OpenAPIFileReader())->readFromAbsoluteFilePath($openAPIFilePath));
        } catch (CannotReadOpenAPI $e) {
            $this->outputErrorBlock($e->getMessage(), $output);
            return Command::FAILURE;
        }
        $output->writeln("\nOpenAPI read successfully\n");

        $processors = [];
        $operationBuilder = new Operation();
        foreach ($openAPI->paths as $path) {
            foreach ($path->operations as $operation) {
                $output->writeln(sprintf('Building Processor for "%s"', $operation->operationId));
                $processors[$operation->operationId] = $operationBuilder->build($operation);
            }
        }
        $output->writeln("\nProcessors built successfully\n");

        if (!file_exists($destination)) {
            mkdir($destination, recursive: true);
        }

        foreach ($processors as $operationId => $processor) {
            $output->writeln(sprintf('caching "%s%s.php"', $destination, $operationId));
            file_put_contents($destination . $operationId . '.php', '<?php return ' . $processor->__toPHP() . ';');
        }
        $output->writeln("\nProcessors cached successfully\n");

        return Command::SUCCESS;
    }

    private function outputErrorBlock(string $message, OutputInterface $output): void
    {
        $formattedMessage = (new FormatterHelper())->formatBlock($message, 'error', true);
        $output->writeLn(sprintf("\n%s\n", $formattedMessage));
    }
}
