<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

class ContentMetrics extends Command
{
    use HasConfig;

    protected function configure(): void
    {
        $this->setName('content:metrics')
            ->setDescription('Calculates the word count for each file and for the book')
            ->addOption(
                name: 'book-dir',
                shortcut: 'd',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The base path where the book files will be created',
                default: '',
            );
    }

    /**
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->init('Content Metrics', $input->getOption('book-dir'))) {
            return Command::INVALID;
        }

        info('✨ Checking files...');

        $result = [];
        $count = 0;
        foreach ($this->disk->files($this->config->getContentPath()) as $file) {
            $markdown = $this->disk->get($file->getPathname());

            $wordCount = str_word_count($markdown);
            $result[] = [$file->getPathname(), $wordCount];
            $count += $wordCount;
        }

        info('✅ Done!');
        info('Check the results');

        table(
            headers: ['FILE', 'WORD COUNT'],
            rows: $result,
        );

        info('✨ BOOK WORD COUNT: ' . $count);

        return Command::SUCCESS;
    }
}
