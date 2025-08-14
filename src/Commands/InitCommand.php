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
        info('Ibis Next - Init');

        $this->disk = new Filesystem();
        $ibisConfigPath = './ibis.php';

        if (file_exists($ibisConfigPath)) {
            info('Config file found, using info from it!');
        } else {
            $title = text(
                label: 'Which will be the book title?',
                placeholder: self::DEFAULT_TITLE,
                required: true,
            );

            $author = text(
                label: 'What is the author name?',
                placeholder: self::DEFAULT_AUTHOR,
                required: true,
            );

            $configContent = file_get_contents('./stubs/ibis.php');
            $configContent = str_replace('{{BOOK_TITLE}}', $title, $configContent);
            $configContent = str_replace('{{BOOK_AUTHOR}}', $author, $configContent);
            $this->disk->put($ibisConfigPath, $configContent);

            info('Config file created!');
        }

        try {
            $this->config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            error($exception->getMessage());

            return Command::FAILURE;
        }

        $contentPath = $this->config->getContentPath();
        $assetsPath = $this->config->getAssetsPath();

        if ($this->disk->isDirectory($assetsPath)) {
            warning('Project is already initialized.');

            return Command::INVALID;
        }

        info('Creating needed files and directories...');
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

        $dirAssetsStubs = './stubs/assets';
        foreach ($assetsToCopy as $asset) {
            $assetStub = "{$dirAssetsStubs}/{$asset}";
            if (file_exists($assetStub)) {
                copy($assetStub, "{$assetsPath}/{$asset}");
            } else {
                warning("File '{$asset}' not found. I will skip this file.");
            }
        }

        info("✨ Creating Content directory at {$contentPath}");

        $this->disk->makeDirectory($contentPath);
        $this->disk->copyDirectory('./stubs/content', $contentPath);

        info('✅ Done!');
        note(
            "You can start building your content (markdown files) into the directory {$contentPath}" . PHP_EOL .
            "You can change the configuration, for example by changing the title, the cover etc. editing the file {$ibisConfigPath}",
        );

        return Command::SUCCESS;
    }
}
