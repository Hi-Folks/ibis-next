<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Ibis\Ibis;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\info;

class SortContentCommand extends Command
{
    use HasConfig;

    protected function configure(): void
    {
        $this->setName('content:sort')
            ->setDescription('Sort the files in the content directory.')
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
        if (!$this->init('Sort Content', $input->getOption('book-dir'))) {
            return Command::INVALID;
        }

        info('✨ Sorting content...');

        foreach ($this->disk->files($this->config->getContentPath()) as $index => $file) {
            $markdown = $this->disk->get($file->getPathname());

            $newName = Str::slug(sprintf(
                '%03d%s',
                (int) $index + 1,
                str_replace(['#', '##', '###'], '', explode("\n", $markdown)[0]),
            ));

            $this->disk->move($file->getPathName(), Ibis::buildPath([$this->config->getContentPath(), "{$newName}.md"]));
        }

        info('✅ Done!');

        return Command::SUCCESS;
    }
}
