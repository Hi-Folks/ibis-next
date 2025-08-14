<?php

namespace Ibis\Concerns;

use Ibis\Config\Font;
use Ibis\Enums\OutputFormat;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait PdfRenderer
{
    protected function buildPdfFile(OutputFormat $outputFormat): string
    {
        $themeName = str_replace('pdf-', '', $outputFormat->value);
        $theme = $this->getTheme($themeName);
        $chapters = $this->buildHtml();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $docConfig = $this->config->getDocument();
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$docConfig->getWidth(), $docConfig->getHeight()],
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
            info("-> ✨ Adding Book Cover {$pathCoverImage} ...");

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
            info("-> ✨ Adding Book Cover {$this->config->getAssetsPath()}/cover.html ...");

            $pdf->WriteHTML($this->disk->get("{$this->config->getAssetsPath()}/cover.html"));
            $pdf->AddPage();
        } else {
            warning("-> No '{$this->config->getAssetsPath()}/cover.jpg' File Found. Skipping ...");
        }

        $pdf->SetHTMLFooter('<div id="footer" style="text-align: center">{PAGENO}</div>');
        $pdf->WriteHTML($theme);

        $headerConfig = $this->config->getHeader();
        foreach ($chapters as $chapter) {
            info("-> ❇️ {$chapter["mdfile"]} ...");

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

        info('-> Writing PDF To Disk ...');
        info("✨✨ {$pdf->page} PDF pages ✨✨");

        $filename = "{$this->config->getExportPath()}/{$this->config->outputFileName()}-{$themeName}{$outputFormat->extension()}";
        $pdf->Output($filename);

        return $filename;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getTheme(string $theme): string
    {
        return $this->disk->get("{$this->config->getAssetsPath()}/theme-{$theme}.html");
    }

    private function fonts(array $fonts, array $fontData): array
    {
        $formatted = [];
        /** @var Font $font */
        foreach ($fonts as $font) {
            $formatted[$font->name] = ['R' => $font->src];
        }

        return [...$fontData, $formatted];
    }
}
