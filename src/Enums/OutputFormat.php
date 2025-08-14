<?php

namespace Ibis\Enums;

enum OutputFormat: string
{
    case EPUB = 'epub';
    case HTML = 'html';
    case PDF_LIGHT = 'pdf-light';
    case PDF_DARK = 'pdf-dark';

    public static function list(): array
    {
        return [
            self::EPUB->value => self::EPUB->label(),
            self::HTML->value => self::HTML->label(),
            self::PDF_LIGHT->value => self::PDF_LIGHT->label(),
            self::PDF_DARK->value => self::PDF_DARK->label(),
        ];
    }

    public function extension(): string
    {
        return match ($this) {
            self::EPUB => '.epub',
            self::HTML => '.html',
            self::PDF_LIGHT, self::PDF_DARK => '.pdf',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EPUB => 'EPUB',
            self::HTML => 'HTML',
            self::PDF_LIGHT => 'PDF (Light Version)',
            self::PDF_DARK => 'PDF (Dark Version)',
        };
    }

    public function builderMethod(): string
    {
        return match ($this) {
            self::EPUB => 'buildEpubFile',
            self::HTML => 'buildHtmlFile',
            self::PDF_LIGHT, self::PDF_DARK => 'buildPdfFile',
        };
    }
}
