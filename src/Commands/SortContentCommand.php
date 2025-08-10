<?php

namespace Ibis\Commands;

use Ibis\Exceptions\InvalidConfigFileException;
use Ibis\Ibis;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SortContentCommand extends Command
{
    private ?Filesystem $disk = null;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('content:sort')
            ->setDescription('Sort the files in the content directory.');
    }

    /**
     * Execute the command.
     *
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disk = new Filesystem();
        $config = null;
        try {
            $config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");
            $output->writeln('<info>Did you run `ibis-next init`?</info>');

            return Command::FAILURE;
        }

        collect($this->disk->files($config->getContentPath()))
            ->each(function ($file, $index) use ($config) {
                $markdown = $this->disk->get($file->getPathname());

                $newName = Str::slug(sprintf(
                    '%03d%s',
                    (int) $index + 1,
                    str_replace(['#', '##', '###'], '', explode("\n", $markdown)[0]),
                ));

                $this->disk->move($file->getPathName(), "{$config->getContentPath()}/{$newName}.md");
            });

        return Command::SUCCESS;
    }
}
