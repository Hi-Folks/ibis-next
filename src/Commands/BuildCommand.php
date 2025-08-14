<?php

namespace Ibis\Commands;

use Exception;
use Ibis\Concerns\EpubRenderer;
use Ibis\Concerns\HasConfig;
use Ibis\Concerns\HtmlRenderer;
use Ibis\Concerns\PathManager;
use Ibis\Concerns\PdfRenderer;
use Ibis\Enums\OutputFormat;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Mpdf\MpdfException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class BuildCommand extends Command
{
    use EpubRenderer;
    use HasConfig;
    use HtmlRenderer;
    use PathManager;
    use PdfRenderer;

    protected function configure(): void
    {
        $this->setName('build')
            ->setDescription('Generates the book.');
    }

    /**
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
                info("✨ Building the {$outputFormat->label()} file ...");

                $createdFiles[$outputFormat->label()] = $this->{$outputFormat->builderMethod()}($outputFormat);
                info('The file was generated successfully!');
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
}
