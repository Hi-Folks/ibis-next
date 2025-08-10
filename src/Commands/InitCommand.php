<?php

namespace Ibis\Commands;

use Ibis\Config;
use Ibis\Exceptions\InvalidConfigFileException;
use Ibis\Ibis;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Mpdf\MpdfException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    private Config $config;

    private ?Filesystem $disk = null;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Initialize a new project in the working directory (current dir by default).');
    }

    /**
     * Execute the command.
     *
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disk = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $io->title('Ibis Next - Init');

        $ibisConfigPath = './ibis.php';
        if (!file_exists($ibisConfigPath)) {
            $io->text('✨ Config file:');
            $io->text("    {$ibisConfigPath}");

            $this->disk->put($ibisConfigPath, __DIR__ . '../../stubs/ibis.php');
        }

        try {
            $this->config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");
            return Command::FAILURE;
        }

        $contentPath = $this->config->getContentPath();
        $assetsPath = $this->config->getAssetsPath();
        $io->section('Creating directory/files');
        $io->text('✨ Config and assets directory:');
        $io->text("    {$assetsPath}");

        if ($this->disk->isDirectory($assetsPath)) {
            $io->newLine();
            $io->warning('Project already initialised!');
            return Command::INVALID;
        }

        $this->disk->makeDirectory($assetsPath);
        $this->disk->makeDirectory($this->config->fontsDir());
        $this->disk->makeDirectory($this->config->imagesDir());

        $assetsToCopy = [
            'cover.jpg',
            'cover-ibis.webp',
            'theme-dark.html',
            'theme-light.html',
            'style.css',
            'highlight.codeblock.min.css',
            'theme-html.html',
            'images/aside-examples.png',
            'images/ibis-next-cover.png',
            'images/ibis-next-setting-page-header.png',
        ];

        $dirAssetsStubs = __DIR__ . '../../stubs/assets';
        foreach ($assetsToCopy as $asset) {
            $assetStub = "{$dirAssetsStubs}/{$asset}";
            if (file_exists($assetStub)) {
                copy($assetStub, "{$assetsPath}/{$asset}/");
            } else {
                $io->warning("File '{$asset}' not found. I will skip this file.");
            }
        }

        $io->text('✨ content directory as:');
        $io->text("    {$contentPath}");

        $this->disk->makeDirectory($contentPath);
        $this->disk->copyDirectory(__DIR__ . '../../stubs/content', $contentPath);

        $io->newLine();
        $io->success('✅ Done!');
        $io->note(
            "You can start building your content (markdown files) into the directory {$contentPath}" . PHP_EOL .
            "You can change the configuration, for example by changing the title, the cover etc. editing the file {$ibisConfigPath}",
        );

        return Command::SUCCESS;
    }
}
