<?php

namespace Ibis\Concerns;

use Ibis\Config\Font;
use Ibis\Enums\OutputFormat;
use Ibis\Ibis;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait PdfRenderer
{
    /**
     * @throws MpdfException
     * @throws FileNotFoundException
     */
    protected function buildPdfFile(OutputFormat $outputFormat, bool $isSample = false): string
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
        $pdf->SetMargins(400, 100, 12);

        $pdf->setAutoTopMargin = 'pad';
        $pdf->setAutoBottomMargin = 'pad';

        $tocLevels = $this->config->getToc()->toArray();
        $pdf->h2toc = $tocLevels;
        $pdf->h2bookmarks = $tocLevels;

        if (! $isSample) {
            $coverConfig = $this->config->getCover();
            $pathCoverImage = Ibis::buildPath([$this->config->getAssetsPath(), $coverConfig->getSrc()]);
            $htmlCover = Ibis::buildPath([$this->config->getAssetsPath(), 'cover.html']);
            if ($this->disk->isFile($pathCoverImage)) {
                info(sprintf('-> ✨ Adding Book Cover %s ...', $pathCoverImage));

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
            } elseif ($this->disk->isFile($htmlCover)) {
                info(sprintf('-> ✨ Adding Book Cover %s ...', $htmlCover));

                $pdf->WriteHTML($this->disk->get($htmlCover));
                $pdf->AddPage();
            } else {
                warning(sprintf("-> No '%s' File Found. Skipping ...", $pathCoverImage));
            }
        }

        $pdf->SetHTMLFooter('<div id="footer" style="text-align: center">{PAGENO}</div>');
        $pdf->WriteHTML($theme);

        $headerConfig = $this->config->getHeader();
        foreach ($chapters as $chapter) {
            info(sprintf('-> ❇️ %s ...', $chapter["mdfile"]));

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
        info(sprintf('✨✨ %s PDF pages ✨✨', $pdf->page));

        if ($isSample) {
            $pdf->WriteHTML('<p style="text-align: center; font-size: 16px; line-height: 40px;">' . $this->config->getSample()->getText() . '</p>');
        }

        $baseName = sprintf('%s-%s%s', $this->config->outputFileName(), $themeName, $outputFormat->extension());
        $filename = Ibis::buildPath([
            $this->config->getExportPath(),
            $isSample ? 'sample-' . $baseName : $baseName,
        ]);
        $pdf->Output($filename);

        return $filename;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getTheme(string $theme): string
    {
        return $this->disk->get(Ibis::buildPath([$this->config->getAssetsPath(), sprintf('theme-%s.html', $theme)]));
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
