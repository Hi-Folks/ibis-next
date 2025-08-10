<?php

use Ibis\Config\Font;
use Ibis\Ibis;

$document = Ibis::document()
    ->width(210)
    ->height(297)
    ->marginLeft(27)
    ->marginRight(27)
    ->marginBottom(14)
    ->marginTop(14);

$toc = Ibis::toc()
    ->h1(0)
    ->h2(0)
    ->h3(1);

$cover = Ibis::cover()
    ->src('cover-ibis.webp')
    ->position('absolute')
    ->width(210)
    ->height(297)
    ->left(0)
    ->right(0)
    ->top(-0.2)
    ->bottom(0);

$header = Ibis::header()
    ->style('font-style: italic; text-align: right; border-bottom: solid 1px #808080')
    ->text('Custom Header');

$sample = Ibis::sample()
    ->text('This is a sample from "Ibis Next: create your eBooks with Markdown" by Roberto Butti.<br>For more information, <a href="https://github.com/Hi-Folks/ibis-next">Click here</a>.')
    ->addPages(1, 7)
    ->addPages(15, 15);

$files = Ibis::files();
//    ->addFile('routing.md')
//    ->addFile('artisan.md');

return Ibis::config()
    ->title('Ibis Next: create your eBooks from Markdown')
    ->author('Roberto B.')
    ->document($document)
    ->toc($toc)
    ->cover($cover)
    ->header($header)
    ->sample($sample)
    ->files($files);
//    ->addFont(new Font('calibri', 'Calibri-Regular.ttf'));
//    ->assetsPath('assets');
//    ->contentPath('content');
//    ->exportPath('export');
//    ->commonMark([]);
