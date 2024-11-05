<?php

declare(strict_types=1);

namespace Membrane\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Logger\ConsoleLogger;
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
            'The directory for the generated processors',
            getcwd() . '/cache'
        );


        self::addOption(
            'namespace',
            null,
            InputOption::VALUE_OPTIONAL,
            'The namespace for the generated processors',
            'Membrane\Cache'
        );

        self::addOption(
            'skip-requests',
            null,
            InputOption::VALUE_NONE,
            'Skip generation of Request processors',
            null,
        );

        self::addOption(
            'skip-responses',
            null,
            InputOption::VALUE_NONE,
            'Skip generation of Response processors',
            null,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $openAPIFilePath = $input->getArgument('openAPI');
        assert(is_string($openAPIFilePath));
        $destination = $input->getArgument('destination');
        assert(is_string($destination));
        $namespace = $input->getOption('namespace');
        assert(is_string($namespace));
        $skipResponses = $input->getOption('skip-responses');
        assert(is_bool($skipResponses));
        $skipRequests = $input->getOption('skip-requests');
        assert(is_bool($skipRequests));

        $consoleLogger = new ConsoleLogger($output);

        if ($skipResponses && $skipRequests) {
            $consoleLogger->warning('Skipping both requests and responses, nothing will be generated');
        }

        $cachingService = new \Membrane\Console\Service\CacheOpenAPIProcessors($consoleLogger);

        $success = $cachingService->cache($openAPIFilePath, $destination, $namespace, !$skipRequests, !$skipResponses);
        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
