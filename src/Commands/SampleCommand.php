<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Ibis\Ibis;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
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

    protected function configure(): void
    {
        $this->setName('sample')
            ->setDescription('Generate a sample from the PDF.')
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

        $themes = $this->buildThemesFromCommand($input);
        if ($themes === []) {
            $themes = multiselect(
                label: 'Which PDF theme would you like to create a sample from?',
                options: [
                    'light' => 'Light',
                    'dark' => 'Dark',
                ],
                required: true,
            );
        }

        $createdFiles = [];
        foreach ($themes as $theme) {
            $filename = $this->buildSampleFile($theme);
            if (is_null($filename)) {
                return command::FAILURE;
            }

            $createdFiles[strtoupper($theme)] = $filename;
        }

        info('✅ Done!');
        info('These are the generated files.');
        $this->showResultTable($createdFiles);

        return Command::SUCCESS;
    }

    /**
     * @throws MpdfException
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws PdfTypeException
     */
    protected function buildSampleFile(string $theme): ?string
    {
        $pdfFilename = Ibis::buildPath([
            $this->config->getExportPath(),
            "{$this->config->outputFileName()}-{$theme}.pdf",
        ]);

        if (!$this->disk->isFile($pdfFilename)) {
            error("⚠️  File {$pdfFilename} not exists (it's needed for creating the sample)");
            warning('Suggestion : try to execute `ibis-next build` before generating a sample');
            return null;
        }

        $pdf = new Mpdf();
        $pdf->setSourceFile($pdfFilename);

        foreach ($this->config->getSample()->pages() as $range) {
            foreach (range($range[0], $range[1]) as $page) {
                $pdf->useTemplate($pdf->importPage($page));
                $pdf->AddPage();
            }
        }

        $pdf->WriteHTML('<p style="text-align: center; font-size: 16px; line-height: 40px;">' . $this->config->getSample()->getText() . '</p>');
        $filename = Ibis::buildPath([
            $this->config->getExportPath(),
            "sample-{$this->config->outputFileName()}-{$theme}.pdf",
        ]);

        info('-> Writing Sample PDF To Disk ...');
        $pdf->Output($filename);

        info("✅ File {$filename} created");
        info("✨✨ {$pdf->page} PDF pages ✨✨");

        return $filename;
    }

    private function buildThemesFromCommand(InputInterface $input): array
    {
        $defaultFlag = $input->getOption('default');
        $lightFlag = $input->getOption('light');
        $darkFlag = $input->getOption('dark');

        $themes = [];
        if ($defaultFlag || $lightFlag) {
            $themes[] = 'light';
        }
        if ($darkFlag) {
            $themes[] = 'dark';
        }

        return $themes;
    }
}
