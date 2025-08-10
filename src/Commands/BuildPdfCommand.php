<?php

namespace Ibis\Commands;

use Ibis\Config\Font;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Mpdf\Mpdf;
use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mpdf\MpdfException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildPdfCommand extends BaseBuildCommand
{
    /**
     * @var string|string[]|null
     */
    public string|array|null $themeName;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('pdf')
            ->setAliases(["build"])
            ->addArgument('theme', InputArgument::OPTIONAL, 'The name of the theme', 'light')
            ->setDescription('Generate the book in PDF format.');
    }

    /**
     * Execute the command.
     *
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->output->writeln('<info>✨ Building PDF file ✨</info>');

        if (!$this->preExecute($input, $output)) {
            return Command::INVALID;
        }

        $this->ensureExportDirectoryExists();
        $this->themeName = $input->getArgument('theme');
        $theme = $this->getTheme($this->themeName);

        $this->buildPdf($this->buildHtml(), $theme);
        $this->output->writeln('');
        $this->output->writeln('<info>Book Built Successfully!</info>');

        return Command::SUCCESS;
    }

    /**
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    protected function buildPdf(Collection $chapters, string $theme): bool
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $docConfig = $this->config->getDocument();
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' =>[$docConfig->getWidth(), $docConfig->getHeight()],
            'margin_left' => $docConfig->getMarginLeft(),
            'margin_right' => $docConfig->getMarginRight(),
            'margin_top' => $docConfig->getMarginTop(),
            'margin_bottom' => $docConfig->getMarginBottom(),
            'fontDir' => array_merge($fontDirs, [$this->config->fontsDir()]),
            'fontdata' => $this->fonts($this->config->getFonts(), $fontData),
        ]);

        $pdf->SetTitle($this->config->getTitle());
        $pdf->SetAuthor($this->config->getAuthor());
        $pdf->SetCreator($this->config->getAuthor());
        $pdf->SetBasePath(realpath($this->config->getContentPath()));

        $pdf->setAutoTopMargin = 'pad';
        $pdf->setAutoBottomMargin = 'pad';

        $tocLevels = $this->config->getToc()->toArray();
        $pdf->h2toc = $tocLevels;
        $pdf->h2bookmarks = $tocLevels;

        $pdf->SetMargins(400, 100, 12);

        $coverConfig = $this->config->getCover();
        $pathCoverImage = "{$this->config->getAssetsPath()}/{$coverConfig->getSrc()}";
        if ($this->disk->isFile($pathCoverImage)) {
            $this->output->writeln('<fg=yellow>==></> Adding Book Cover ...');

            $coverPosition = $coverConfig->positionStyle();
            $coverDimensions = $coverConfig->dimensionsStyle();
            $coverImageAbsPath = realpath($pathCoverImage);
            $pdf->WriteHTML(
                <<<HTML
<div style="{$coverPosition}">
    <img src="{$coverImageAbsPath}" style="{$coverDimensions}"/>
</div>
HTML,
            );

            $pdf->AddPage();
        } elseif ($this->disk->isFile("{$this->config->getAssetsPath()}/cover.html")) {
            $this->output->writeln('<fg=yellow>==></> Adding Book Cover ...');

            $pdf->WriteHTML($this->disk->get("{$this->config->getAssetsPath()}/cover.html"));
            $pdf->AddPage();
        } else {
            $this->output->writeln("<fg=red>==></>No '{$this->config->getAssetsPath()}/cover.jpg' File Found. Skipping ...");
        }

        $pdf->SetHTMLFooter('<div id="footer" style="text-align: center">{PAGENO}</div>');
        $this->output->writeln('<fg=yellow>==></> Building PDF ...');
        $pdf->WriteHTML($theme);

        $headerConfig = $this->config->getHeader();
        foreach ($chapters as $chapter) {
            $this->output->writeln("<fg=yellow>==></> ❇️ {$chapter["mdfile"]}  ...");

            $pdf->SetHTMLHeader(
                '
                    <div style="' . $headerConfig->getStyle() . '">
                        ' . Arr::get($chapter, "frontmatter.title", $this->config->getTitle()) . '
                    </div>',
            );

            $pdf->WriteHTML($chapter["html"]);
        }

        $pdf->SetHTMLHeader(
            '
                    <div style="' . $headerConfig->getStyle() . '">
                        ' . $this->config->getTitle() . '
                    </div>',
        );

        $this->output->writeln('<fg=yellow>==></> Writing PDF To Disk ...');
        $this->output->writeln('');
        $this->output->writeln("✨✨ {$pdf->page} PDF pages ✨✨");

        $pdfFilename = "{$this->config->getExportPath()}/{$this->config->outputFileName()}.pdf";
        $pdf->Output($pdfFilename);

        $this->output->writeln("<fg=green>==></> PDF file {$pdfFilename} created");
        return true;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getTheme(string $themeName): string
    {
        return $this->disk->get("{$this->config->getAssetsPath()}/theme-{$themeName}.html");
    }

    protected function fonts(array $fonts, array $fontData): array
    {
        $formatted = [];
        /** @var Font $font */
        foreach ($fonts as $font) {
            $formatted[$font->name] = ['R' => $font->src];
        }

        return [...$fontData, $formatted];
    }
}
