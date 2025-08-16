<?php

namespace Ibis\Commands;

use Exception;
use Ibis\Concerns\EpubRenderer;
use Ibis\Concerns\HasConfig;
use Ibis\Concerns\HtmlRenderer;
use Ibis\Concerns\PdfRenderer;
use Ibis\Enums\OutputFormat;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Mpdf\MpdfException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class BuildCommand extends Command
{
    use EpubRenderer;
    use HasConfig;
    use HtmlRenderer;
    use PdfRenderer;

    protected function configure(): void
    {
        $this->setName('build')
            ->setDescription('Generates the book.')
            ->addOption(name: 'epub', description: 'Generates an .epub version of the book')
            ->addOption(name: 'html', description: 'Generates an .html version of the book')
            ->addOption(name: 'pdf', description: 'Generates an .pdf version of the book using the light theme')
            ->addOption(name: 'pdf-light', description: 'Generates an .pdf version of the book using the light theme')
            ->addOption(name: 'pdf-dark', description: 'Generates an .pdf version of the book using the dark theme')
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
     * @throws MpdfException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->init(bookDir: $input->getOption('book-dir'))) {
            return Command::INVALID;
        }

        $outputFormats = $this->buildOutputFormatsFromCommand($input);
        if ($outputFormats === []) {
            $outputFormats = multiselect(
                label: 'Which output formats you want to build?',
                options: OutputFormat::list(),
                required: true,
            );
        }

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

    private function buildOutputFormatsFromCommand(InputInterface $input): array
    {
        $epubFlag = $input->getOption('epub');
        $htmlFlag = $input->getOption('html');
        $pdfFlag = $input->getOption('pdf');
        $pdfLightFlag = $input->getOption('pdf-light');
        $pdfDarkFlag = $input->getOption('pdf-dark');

        $outputFormats = [];
        if ($epubFlag) {
            $outputFormats[] = OutputFormat::EPUB->value;
        }
        if ($htmlFlag) {
            $outputFormats[] = OutputFormat::HTML->value;
        }
        if ($pdfFlag || $pdfLightFlag) {
            $outputFormats[] = OutputFormat::PDF_LIGHT->value;
        }
        if ($pdfDarkFlag) {
            $outputFormats[] = OutputFormat::PDF_DARK->value;
        }

        return $outputFormats;
    }

    private function ensureExportDirectoryExists(): void
    {
        info('Preparing export directory ...');
        $exportDir = $this->config->getExportPath();

        if (!$this->disk->isDirectory($exportDir)) {
            $this->disk->makeDirectory($exportDir, 0755, true);
        }
    }
}
