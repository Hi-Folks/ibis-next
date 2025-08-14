<?php

namespace Ibis\Commands;

use Exception;
use Ibis\Concerns\HtmlRenderer;
use Ibis\Concerns\PathManager;
use Ibis\Config;
use Ibis\Enums\OutputFormat;
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
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\table;

class BuildCommand extends Command
{
    use HtmlRenderer;
    use PathManager;

    protected Filesystem $disk;

    protected string $currentPath;

    protected Config $config;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Generates the book.');
    }

    /**
     * Execute the command.
     *
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->init()) {
            return Command::INVALID;
        }

        $outputFormats = multiselect(
            label: 'Which output formats you want to build?',
            options: OutputFormat::list(),
            required: true,
        );

        $this->ensureExportDirectoryExists();
        $createdFiles = [];
        foreach ($outputFormats as $outputFormat) {
            try {
                $outputFormat = OutputFormat::from($outputFormat);
                info("✨ Building the {$outputFormat->label()} file...");

                $this->{$outputFormat->builderMethod()}($outputFormat);
                $createdFiles[$outputFormat->label()] = "{$this->config->getExportPath()}/{$this->config->outputFileName()}{$outputFormat->extension()}";

                info('The file was generated successfully');
                info('-----');
            } catch (Exception $exception) {
                error("Failed to build the {$outputFormat->label()} file - {$exception->getMessage()}");

                continue;
            }

        }

        info('✅ Done!');
        info('These are the generated files.');
        $this->showResultTable($createdFiles);

        return Command::SUCCESS;
    }

    protected function init(): bool
    {
        info('Ibis Next - Book Build');

        $this->disk = new Filesystem();

        try {
            $this->config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            error($exception->getMessage());
            info('Did you run `ibis-next init`?');

            return false;
        }

        info('✨ Loading config/assets from current directory...');
        info('✨ Loading config file from: ./ibis.php ...');

        $contentPath = $this->config->getContentPath();
        if (!file_exists($contentPath) || !is_dir($contentPath)) {
            error("Error, check if {$contentPath} exists");

            return false;
        }

        info("✨ Loading content from: {$contentPath}...");

        return true;
    }

    protected function showResultTable(array $createdFiles): void
    {
        $formatted = [];
        foreach ($createdFiles as $outputFormat => $file) {
            $formatted[] = [$outputFormat, $file];
        }

        table(
            headers: ['OUTPUT FORMAT', 'FILE'],
            rows: $formatted,
        );
    }
}
