<?php

namespace Rikudou\JsonApiBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InstallRoutesCommand extends Command
{
    public function __construct(
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'force',
                mode: InputOption::VALUE_NONE,
                description: 'Will overwrite the routes file even if it exists.',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = "{$this->projectDir}/config/routes/rikudou_json_api.yaml";
        if (file_exists($file) && !$input->getOption('force')) {
            $io->warning('Routes file already exists, use --force to overwrite.');

            return Command::SUCCESS;
        }

        $content = <<<EOF
            rikudou_json_api:
                resource: '@RikudouJsonApiBundle/Resources/routes.yaml'
            EOF;

        if (!file_put_contents($file, $content)) {
            $io->error('Unable to write routes file.');

            return Command::FAILURE;
        }

        $io->success('Routes file successfully created.');

        return Command::SUCCESS;
    }
}
