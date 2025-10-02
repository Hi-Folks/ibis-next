<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Ibis\Concerns\HtmlRenderer;
use Ibis\Concerns\PdfRenderer;
use Ibis\Config\FileList;
use Ibis\Enums\OutputFormat;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class SampleCommand extends Command
{
    use HasConfig;
    use HtmlRenderer;
    use PdfRenderer;

    protected function configure(): void
    {
        $this->setName('sample')
            ->setDescription('Generates a sample from the PDF')
            ->addOption(name: 'default', description: 'Generates an .pdf sample of the book using the light theme')
            ->addOption(name: 'light', description: 'Generates an .pdf sample of the book using the light theme')
            ->addOption(name: 'dark', description: 'Generates an .pdf sample of the book using the dark theme')
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
        if (!$this->init('Generate Sample', $input->getOption('book-dir'))) {
            return Command::INVALID;
        }

        if ($this->config->getSample()->files() === []) {
            warning(sprintf('No sample files configured. Update your %s file first and run again.', $this->config->configFilePath()));

            return Command::FAILURE;
        }

        $fileList = new FileList();
        foreach ($this->config->getSample()->files() as $file) {
            $fileList->addFile($file);
        }

        $this->config->files($fileList);

        $themes = $this->buildThemesFromCommand($input);
        if ($themes === []) {
            $themes = multiselect(
                label: 'Which PDF theme would you like to create a sample from?',
                options: [
                    'pdf-light' => 'Light',
                    'pdf-dark' => 'Dark',
                ],
                required: true,
            );
        }

        $createdFiles = [];
        foreach ($themes as $theme) {

            try {
                $filename = $this->buildPdfFile(OutputFormat::from($theme), true);
            } catch (\Exception $e) {
                error("Error in building PDF files. " . $e->getMessage());
                return command::FAILURE;
            }

            $createdFiles[strtoupper((string) $theme)] = $filename;
        }

        info('✅ Done!');
        info('These are the generated files.');
        $this->showResultTable($createdFiles);

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function buildThemesFromCommand(InputInterface $input): array
    {
        $defaultFlag = $input->getOption('default');
        $lightFlag = $input->getOption('light');
        $darkFlag = $input->getOption('dark');

        $themes = [];
        if ($defaultFlag || $lightFlag) {
            $themes[] = 'pdf-light';
        }

        if ($darkFlag) {
            $themes[] = 'pdf-dark';
        }

        return $themes;
    }
}
