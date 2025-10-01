<?php

namespace Ibis\Commands;

use Ibis\Config;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    private ?\Illuminate\Filesystem\Filesystem $disk = null;


    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->addOption(
                'workingdir',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The path of the working directory where `ibis.php` and `assets` directory will be created',
                '',
            )
            ->setDescription('Initialize a new project in the working directory (current dir by default).');
    }

    /**
     * Execute the command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Mpdf\MpdfException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disk = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $io->title('Ibis Next - Init');

        $workingPath = $input->getOption('workingdir');
        if ($workingPath === "") {
            $workingPath = "./";
        } elseif (!is_dir($workingPath)) {
            $io->warning("The working directory " . $workingPath . " doesn't exists.");
            $confirm = $io->choice(
                'Do you want to create it (or exit, or use current dir)?',
                ["yes" => "Yes","exit" => "Exit", "current" => "Use the current directory"],
                "yes",
            );
            switch ($confirm) {
                case "yes":
                    mkdir($workingPath, recursive: true);
                    $io->text("Created the <strong>" . $workingPath . "</strong> directory.");
                    break;
                case "current":
                    $workingPath = "./";
                    $io->text("Using the " . $workingPath . " directory.");
                    break;
                default:
                    $io->text("Exit. bye");
                    return Command::INVALID;
            }
        }

        $ibisConfigPath = Config::buildPath(
            $workingPath,
            'ibis.php',
        );
        $contentPath = Config::buildPath(
            $workingPath,
            'content',
        );
        $assetsPath = Config::buildPath(
            $workingPath,
            'assets',
        );
        $io->section('Creating directory/files');

        $io->text('✨ Config and assets directory:');
        $io->text('    ' . $assetsPath);



        if ($this->disk->isDirectory($assetsPath)) {
            $io->newLine();
            $io->warning('Project already initialised!');
            return Command::INVALID;
        }

        $this->disk->makeDirectory(
            $assetsPath,
        );

        $this->disk->makeDirectory(
            Config::buildPath($assetsPath, 'fonts'),
        );
        $this->disk->makeDirectory(
            Config::buildPath($assetsPath, 'images'),
        );

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
        $dirAssetsStubs = Config::buildPath(
            __DIR__,
            '..',
            '..',
            'stubs/assets',
        );
        Config::buildPath(
            $dirAssetsStubs,
            'images',
        );


        foreach ($assetsToCopy as $asset) {

            $assetStub = Config::buildPath(
                $dirAssetsStubs,
                $asset,
            );
            if (file_exists($assetStub)) {
                copy(
                    $assetStub,
                    Config::buildPath($assetsPath, $asset),
                );
            } else {
                $io->warning(sprintf("File '%s' not found. I will skip this file.", $asset));
            }
        }

        $io->text('✨ content directory as:');
        $io->text('    ' . $contentPath . '');

        $this->disk->makeDirectory(
            $contentPath,
        );

        $this->disk->copyDirectory(
            Config::buildPath(
                __DIR__,
                '../../stubs/content',
            ),
            $contentPath,
        );

        $io->text('✨ Config file:');
        $io->text('    ' . $ibisConfigPath);

        $this->disk->put(
            $ibisConfigPath,
            $this->disk->get(Config::buildPath(
                __DIR__,
                '../../stubs/ibis.php',
            )),
        );

        $io->newLine();
        $io->success('✅ Done!');
        $io->note(
            'You can start building your content (markdown files) into the directory ' . $contentPath . PHP_EOL
            . "You can change the configuration, for example by changing the title, the cover etc. editing the file " . $ibisConfigPath,
        );

        return Command::SUCCESS;
    }
}
