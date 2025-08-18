<?php

namespace Ibis\Commands;

use Ibis\Config;
use Ibis\Exceptions\InvalidConfigFileException;
use Ibis\Ibis;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class InitCommand extends Command
{
    private const DEFAULT_TITLE = 'Ibis Next: create your eBooks from Markdown';

    private const DEFAULT_AUTHOR = 'Roberto B.';

    private Config $config;

    private ?Filesystem $disk = null;

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initializes a new project in the working directory (current dir by default)')
            ->addOption(name: 'default', description: 'Creates the config with the default values')
            ->addOption(name: 'json', description: 'Uses a JSON file format for the config instead a PHP one')
            ->addOption(
                name: 'book-dir',
                shortcut: 'd',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The base path where the book files will be created',
                default: '',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        info('Ibis Next - Init');
        $this->disk = new Filesystem();

        $bookDir = $input->getOption('book-dir');
        $basePath = Ibis::basePath();
        $baseBookPath = Ibis::buildPath([$basePath, $bookDir]);

        if (! file_exists($baseBookPath) || ! is_dir($baseBookPath)) {
            warning("The path '{$baseBookPath}' does not exist or is not a directory.");
            info("✨ Creating directory '{$baseBookPath}'...");

            mkdir($baseBookPath, recursive: true);
        }

        $useJSONConfig = $input->getOption('json');
        $ibisConfigPath = Ibis::buildPath([
            $baseBookPath,
            $useJSONConfig ? Ibis::JSON_CONFIG_FILE : Ibis::PHP_CONFIG_FILE,
        ]);

        if (file_exists($ibisConfigPath)) {
            info('Config file found, using info from it!');
        } else {
            $useDefault = $input->getOption('default');

            $title = $useDefault
                ? self::DEFAULT_TITLE
                : text(
                    label: 'Which will be the book title?',
                    placeholder: self::DEFAULT_TITLE,
                    required: true,
                );

            $author = $useDefault
                ? self::DEFAULT_AUTHOR
                : text(
                    label: 'What is the author name?',
                    placeholder: self::DEFAULT_AUTHOR,
                    required: true,
                );

            $this->createConfigFile($basePath, $useJSONConfig, $ibisConfigPath, $title, $author);
        }

        try {
            $this->config = Ibis::loadConfig($basePath, $bookDir);
        } catch (InvalidConfigFileException $exception) {
            error($exception->getMessage());

            return Command::FAILURE;
        }

        if ($this->disk->isDirectory($this->config->getAssetsPath())) {
            warning('Project is already initialized.');

            return Command::INVALID;
        }

        info('Creating needed files and directories...');
        $this->createAssetsDirectory($basePath);
        $this->createContentDirectory($basePath);

        info('✅ Done!');
        note(
            "You can start building your content (markdown files) into the directory {$this->config->getContentPath()}"
            . PHP_EOL
            . "You can change the configuration, for example by changing the title, the cover etc. editing the file {$ibisConfigPath}",
        );

        return Command::SUCCESS;
    }

    private function createConfigFile(
        string $basePath,
        bool $useJSONConfig,
        string $ibisConfigPath,
        string $title,
        string $author,
    ): void {
        $configContent = file_get_contents(Ibis::buildPath([
            $basePath,
            'stubs',
            $useJSONConfig ? Ibis::JSON_CONFIG_FILE : Ibis::PHP_CONFIG_FILE,
        ]));
        $configContent = str_replace('{{BOOK_TITLE}}', $title, $configContent);
        $configContent = str_replace('{{BOOK_AUTHOR}}', $author, $configContent);
        $this->disk->put($ibisConfigPath, $configContent);

        info('Config file created!');
    }

    private function createAssetsDirectory(string $basePath): void
    {
        $assetsPath = $this->config->getAssetsPath();
        info("✨ Creating Assets directory at {$assetsPath}");

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

        $dirAssetsStubs = Ibis::buildPath([$basePath, 'stubs', 'assets']);
        foreach ($assetsToCopy as $asset) {
            $assetStub = Ibis::buildPath([$dirAssetsStubs, $asset]);
            if (file_exists($assetStub)) {
                copy($assetStub, Ibis::buildPath([$assetsPath, $asset]));
            } else {
                warning("File '{$asset}' not found. I will skip this file.");
            }
        }
    }

    private function createContentDirectory(string $basePath): void
    {
        $contentPath = $this->config->getContentPath();
        info("✨ Creating Content directory at {$contentPath}");

        $this->disk->makeDirectory($contentPath);
        $this->disk->copyDirectory(Ibis::buildPath([$basePath, 'stubs', 'content']), $contentPath);
    }
}
