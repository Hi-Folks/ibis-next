<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\info;

class SortContentCommand extends Command
{
    use HasConfig;

    protected function configure(): void
    {
        $this->setName('content:sort')
            ->setDescription('Sort the files in the content directory.');
    }

    /**
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->init('Sort Content')) {
            return Command::INVALID;
        }

        info('✨ Sorting content...');

        collect($this->disk->files($this->config->getContentPath()))
            ->each(function ($file, $index) {
                $markdown = $this->disk->get($file->getPathname());

                $newName = Str::slug(sprintf(
                    '%03d%s',
                    (int) $index + 1,
                    str_replace(['#', '##', '###'], '', explode("\n", $markdown)[0]),
                ));

                $this->disk->move($file->getPathName(), "{$this->config->getContentPath()}/{$newName}.md");
            });

        info('✅ Done!');

        return Command::SUCCESS;
    }
}
