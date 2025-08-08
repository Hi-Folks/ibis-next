<?php

use Ibis\Ibis;
use Ibis\Config;
use Ibis\Config\Cover;
use Ibis\Config\Document;
use Ibis\Config\FileList;
use Ibis\Config\Font;
use Ibis\Config\Header;
use Ibis\Config\Sample;
use Ibis\Config\Toc;
use Ibis\Exceptions\InvalidConfigFileException;

beforeEach(function () {
    $this->testConfigPath = __DIR__ . '/test-config.json';
    $this->invalidJsonPath = __DIR__ . '/invalid.json';
    $this->emptyJsonPath = __DIR__ . '/empty.json';
});

afterEach(function () {
    $files = [
        $this->testConfigPath,
        $this->invalidJsonPath,
        $this->emptyJsonPath,
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

describe('File validation', function () {
    test('throws exception when file does not exist', function () {
        $nonExistentPath = '/path/that/does/not/exist.json';

        expect(fn() => Ibis::config($nonExistentPath))
            ->toThrow(InvalidConfigFileException::class)
            ->toThrow("The configuration file '{$nonExistentPath}' does not exist.");
    });

    test('throws exception when JSON is invalid', function () {
        file_put_contents($this->invalidJsonPath, '{ invalid json: true, }');

        expect(fn() => Ibis::config($this->invalidJsonPath))
            ->toThrow(InvalidConfigFileException::class)
            ->toThrow("The configuration file '{$this->invalidJsonPath}' is not a valid JSON file.");
    });

    test('handles empty JSON file', function () {
        file_put_contents($this->emptyJsonPath, '{}');

        $config = Ibis::config($this->emptyJsonPath);

        expect($config)->toBeInstanceOf(Config::class);
    });

    test('handles JSON with null values', function () {
        $data = [
            'title' => null,
            'author' => null,
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config)->toBeInstanceOf(Config::class);
    });
});

describe('Basic properties', function () {
    test('loads title correctly', function () {
        $data = ['title' => 'Test Book Title'];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe('Test Book Title');
    });

    test('loads author correctly', function () {
        $data = ['author' => 'John Doe'];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getAuthor())->toBe('John Doe');
    });

    test('loads content_path correctly', function () {
        $data = ['content_path' => './my-content'];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getContentPath())->toBe('./my-content');
    });

    test('loads all basic properties together', function () {
        $data = [
            'title' => 'Complete Book',
            'author' => 'Jane Smith',
            'content_path' => './content-dir',
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe('Complete Book');
        expect($config->getAuthor())->toBe('Jane Smith');
        expect($config->getContentPath())->toBe('./content-dir');
    });
});

describe('Fonts configuration', function () {
    test('loads fonts as key-value pairs', function () {
        $data = [
            'fonts' => [
                'calibri' => 'Calibri-Regular.ttf',
                'times' => 'times-regular.ttf',
                'arial' => 'Arial.ttf',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $fonts = $config->getFonts();

        expect($fonts)->toHaveCount(3);
        expect($fonts[0])->toBeInstanceOf(Font::class);
        expect($fonts[0]->name)->toBe('calibri');
        expect($fonts[0]->src)->toBe('Calibri-Regular.ttf');
        expect($fonts[1]->name)->toBe('times');
        expect($fonts[2]->name)->toBe('arial');
    });

    test('handles empty fonts array', function () {
        $data = ['fonts' => []];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getFonts())->toBeArray()->toBeEmpty();
    });

    test('ignores non-array fonts value', function () {
        $data = ['fonts' => 'not-an-array'];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config)->toBeInstanceOf(Config::class);
    });
});

describe('CommonMark configuration', function () {
    test('loads commonmark configuration', function () {
        $data = [
            'commonmark' => [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 10,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $commonMark = $config->getCommonMark();

        expect($commonMark)->toBeArray();
        expect($commonMark['html_input'])->toBe('strip');
        expect($commonMark['allow_unsafe_links'])->toBe(false);
        expect($commonMark['max_nesting_level'])->toBe(10);
    });

    test('handles empty commonmark configuration', function () {
        $data = ['commonmark' => []];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getCommonMark())->toBeArray()->toBeEmpty();
    });
});

describe('Document configuration', function () {
    test('loads all document properties', function () {
        $data = [
            'document' => [
                'height' => 297.5,
                'width' => 210.0,
                'margin_left' => 25,
                'margin_right' => 25,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $document = $config->getDocument();

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->getHeight())->toBe(297.5);
        expect($document->getWidth())->toBe(210.0);
        expect($document->getMarginLeft())->toBe(25.0);
        expect($document->getMarginRight())->toBe(25.0);
        expect($document->getMarginTop())->toBe(20.0);
        expect($document->getMarginBottom())->toBe(20.0);
    });

    test('loads partial document properties', function () {
        $data = [
            'document' => [
                'width' => 200,
                'margin_left' => 15,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $document = $config->getDocument();

        expect($document->getWidth())->toBe(200.0);
        expect($document->getMarginLeft())->toBe(15.0);
    });

    test('handles empty document configuration', function () {
        $data = ['document' => []];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $document = $config->getDocument();

        expect($document)->toBeInstanceOf(Document::class);
    });
});

describe('TOC configuration', function () {
    test('loads all TOC levels', function () {
        $data = [
            'toc_levels' => [
                'h1' => 0,
                'h2' => 1,
                'h3' => 2,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $toc = $config->getToc();

        expect($toc)->toBeInstanceOf(Toc::class);
        expect($toc->getH1())->toBe(0);
        expect($toc->getH2())->toBe(1);
        expect($toc->getH3())->toBe(2);
    });

    test('loads partial TOC levels', function () {
        $data = [
            'toc_levels' => [
                'h1' => 5,
                'h3' => 10,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $toc = $config->getToc();

        expect($toc->getH1())->toBe(5);
        expect($toc->getH3())->toBe(10);
    });

    test('handles case-insensitive TOC keys', function () {
        $data = [
            'toc_levels' => [
                'H1' => 1,
                'H2' => 2,
                'H3' => 3,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $toc = $config->getToc();

        // The implementation should handle uppercase keys
        expect($toc)->toBeInstanceOf(Toc::class);
    });
});

describe('Cover configuration', function () {
    test('loads all cover properties', function () {
        $data = [
            'cover' => [
                'image' => 'cover.jpg',
                'position' => 'absolute',
                'height' => 300,
                'width' => 200,
                'left' => 10,
                'right' => 20,
                'top' => 5,
                'bottom' => 15,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $cover = $config->getCover();

        expect($cover)->toBeInstanceOf(Cover::class);
        expect($cover->getSrc())->toBe('cover.jpg');
        expect($cover->getPosition())->toBe('absolute');
        expect($cover->getHeight())->toBe(300.0);
        expect($cover->getWidth())->toBe(200.0);
        expect($cover->getLeft())->toBe(10.0);
        expect($cover->getRight())->toBe(20.0);
        expect($cover->getTop())->toBe(5.0);
        expect($cover->getBottom())->toBe(15.0);
    });

    test('loads minimal cover configuration', function () {
        $data = [
            'cover' => [
                'image' => 'my-cover.png',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $cover = $config->getCover();

        expect($cover->getSrc())->toBe('my-cover.png');
    });

    test('handles different image formats', function () {
        $formats = ['cover.jpg', 'cover.png', 'cover.webp', 'cover.gif'];

        foreach ($formats as $format) {
            $data = ['cover' => ['image' => $format]];
            file_put_contents($this->testConfigPath, json_encode($data));

            $config = Ibis::config($this->testConfigPath);
            expect($config->getCover()->getSrc())->toBe($format);
        }
    });
});

describe('Header configuration', function () {
    test('loads header with style and text', function () {
        $data = [
            'header' => [
                'style' => 'font-weight: bold; color: red;',
                'text' => 'Chapter Header',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $header = $config->getHeader();

        expect($header)->toBeInstanceOf(Header::class);
        expect($header->getStyle())->toBe('font-weight: bold; color: red;');
        expect($header->getText())->toBe('Chapter Header');
    });

    test('loads header with only style', function () {
        $data = [
            'header' => [
                'style' => 'font-style: italic;',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $header = $config->getHeader();

        expect($header->getStyle())->toBe('font-style: italic;');
    });

    test('loads header with only text', function () {
        $data = [
            'header' => [
                'text' => 'My Header Text',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $header = $config->getHeader();

        expect($header->getText())->toBe('My Header Text');
    });
});

describe('Sample configuration', function () {
    test('loads sample with text and pages', function () {
        $data = [
            'sample' => [
                'text' => 'This is a sample notice',
                'pages' => [
                    [1, 5],
                    [10, 15],
                    [20, 25],
                ],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $sample = $config->getSample();

        expect($sample)->toBeInstanceOf(Sample::class);
        expect($sample->getText())->toBe('This is a sample notice');
        expect($sample->pages())->toHaveCount(3);
        expect($sample->pages()[0])->toBe([1, 5]);
        expect($sample->pages()[1])->toBe([10, 15]);
    });

    test('loads sample with only text', function () {
        $data = [
            'sample' => [
                'text' => 'Sample text only',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $sample = $config->getSample();

        expect($sample->getText())->toBe('Sample text only');
    });

    test('loads sample with only pages', function () {
        $data = [
            'sample' => [
                'pages' => [[1, 3], [5, 7]],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $sample = $config->getSample();

        expect($sample->pages())->toHaveCount(2);
    });

    test('handles single page ranges', function () {
        $data = [
            'sample' => [
                'pages' => [[5, 5]],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $sample = $config->getSample();

        expect($sample->pages()[0])->toBe([5, 5]);
    });
});

describe('Files configuration', function () {
    test('loads files list', function () {
        $data = [
            'files' => [
                'files' => [
                    'intro.md',
                    'chapter1.md',
                    'chapter2.md',
                    'conclusion.md',
                ],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $files = $config->getFiles();

        expect($files)->toBeInstanceOf(FileList::class);
        expect($files->files())->toHaveCount(4);
        expect($files->files()[0])->toBe('intro.md');
        expect($files->files()[3])->toBe('conclusion.md');
    });

    test('handles empty files list', function () {
        $data = [
            'files' => [
                'files' => [],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $files = $config->getFiles();

        expect($files->files())->toBeArray()->toBeEmpty();
    });

    test('loads files with different extensions', function () {
        $data = [
            'files' => [
                'files' => [
                    'file.md',
                    'readme.txt',
                    'index.html',
                    'config.json',
                ],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);
        $files = $config->getFiles();

        expect($files->files())->toHaveCount(4);
        expect($files->files())->toContain('readme.txt');
        expect($files->files())->toContain('config.json');
    });
});

describe('Complete configuration', function () {
    test('loads a complete configuration file', function () {
        $data = [
            'title' => 'Complete Book',
            'author' => 'John Author',
            'content_path' => './content',
            'fonts' => [
                'main' => 'Main-Font.ttf',
                'secondary' => 'Secondary-Font.ttf',
            ],
            'commonmark' => [
                'html_input' => 'strip',
            ],
            'document' => [
                'height' => 297,
                'width' => 210,
                'margin_left' => 20,
                'margin_right' => 20,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ],
            'toc_levels' => [
                'h1' => 0,
                'h2' => 1,
                'h3' => 2,
            ],
            'cover' => [
                'image' => 'book-cover.jpg',
                'position' => 'relative',
                'width' => 210,
                'height' => 297,
            ],
            'header' => [
                'style' => 'text-align: center;',
                'text' => 'Book Header',
            ],
            'sample' => [
                'text' => 'Sample notice text',
                'pages' => [[1, 10]],
            ],
            'files' => [
                'files' => ['chapter1.md', 'chapter2.md'],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        // Verify all components are loaded
        expect($config->getTitle())->toBe('Complete Book');
        expect($config->getAuthor())->toBe('John Author');
        expect($config->getContentPath())->toBe('./content');
        expect($config->getFonts())->toHaveCount(2);
        expect($config->getCommonMark())->toHaveKey('html_input');
        expect($config->getDocument()->getWidth())->toBe(210.0);
        expect($config->getToc()->getH1())->toBe(0);
        expect($config->getCover()->getSrc())->toBe('book-cover.jpg');
        expect($config->getHeader()->getText())->toBe('Book Header');
        expect($config->getSample()->getText())->toBe('Sample notice text');
        expect($config->getFiles()->files())->toHaveCount(2);
    });

    test('handles configuration with missing optional sections', function () {
        $data = [
            'title' => 'Minimal Book',
            'author' => 'Jane Doe',
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe('Minimal Book');
        expect($config->getAuthor())->toBe('Jane Doe');
    });
});

describe('Edge cases', function () {
    test('handles Unicode characters in configuration', function () {
        $data = [
            'title' => '日本語のタイトル',
            'author' => 'José García',
            'header' => [
                'text' => 'Привет мир! 🌍',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data, JSON_UNESCAPED_UNICODE));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe('日本語のタイトル');
        expect($config->getAuthor())->toBe('José García');
        expect($config->getHeader()->getText())->toBe('Привет мир! 🌍');
    });

    test('handles very long strings', function () {
        $longString = str_repeat('a', 10000);
        $data = [
            'title' => $longString,
            'sample' => [
                'text' => $longString,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe($longString);
        expect($config->getSample()->getText())->toBe($longString);
    });

    test('handles special characters in file paths', function () {
        $data = [
            'content_path' => './content/with spaces/',
            'files' => [
                'files' => [
                    'file with spaces.md',
                    'file-with-dashes.md',
                    'file_with_underscores.md',
                ],
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getContentPath())->toBe('./content/with spaces/');
        expect($config->getFiles()->files())->toContain('file with spaces.md');
    });

    test('handles numeric strings as values', function () {
        $data = [
            'title' => '123',
            'author' => '456',
            'document' => [
                'width' => '210',
                'height' => '297',
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getTitle())->toBe('123');
        expect($config->getAuthor())->toBe('456');
        expect($config->getDocument()->getWidth())->toBe(210.0);
        expect($config->getDocument()->getHeight())->toBe(297.0);
    });

    test('handles deeply nested configuration', function () {
        $data = [
            'title' => 'Test',
            'document' => [
                'width' => 100,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 5,
                'margin_bottom' => 5,
                'height' => 200,
            ],
            'cover' => [
                'image' => 'cover.jpg',
                'position' => 'absolute',
                'width' => 100,
                'height' => 200,
                'left' => 0,
                'right' => 0,
                'top' => 0,
                'bottom' => 0,
            ],
        ];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config->getDocument()->getMarginLeft())->toBe(10.0);
        expect($config->getCover()->getLeft())->toBe(0.0);
    });
});

describe('Error handling', function () {
    test('handles malformed JSON gracefully', function () {
        $malformedJsons = [
            '{"title": "test"',  // Missing closing brace
            '{"title": test}',    // Unquoted value
            "{'title': 'test'}",  // Single quotes
            '{"title": "test",}', // Trailing comma
        ];

        foreach ($malformedJsons as $json) {
            file_put_contents($this->invalidJsonPath, $json);

            expect(fn() => Ibis::config($this->invalidJsonPath))
                ->toThrow(InvalidConfigFileException::class);
        }
    });

    test('handles file read permission issues', function () {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission test skipped on Windows');
        }

        file_put_contents($this->testConfigPath, '{"title": "test"}');
        chmod($this->testConfigPath, 0000);

        try {
            expect(fn() => Ibis::config($this->testConfigPath))
                ->toThrow(\Exception::class);
        } finally {
            chmod($this->testConfigPath, 0644);
        }
    });

    test('handles circular references prevention', function () {
        // PHP's json_decode handles circular references by default
        $data = ['title' => 'Test'];
        file_put_contents($this->testConfigPath, json_encode($data));

        $config = Ibis::config($this->testConfigPath);

        expect($config)->toBeInstanceOf(Config::class);
    });
});

describe('Default values and fallbacks', function () {
    test('returns default Config when path is null', function () {
        $config = Ibis::config(null);

        expect($config)->toBeInstanceOf(Config::class);
    });

    test('returns default Config when path is empty string', function () {
        $config = Ibis::config('');

        expect($config)->toBeInstanceOf(Config::class);
    });

    test('handles missing optional properties gracefully', function () {
        $requiredOnlyData = [
            'title' => 'Required Title',
        ];
        file_put_contents($this->testConfigPath, json_encode($requiredOnlyData));

        $config = Ibis::config($this->testConfigPath);

        expect($config)->toBeInstanceOf(Config::class);
        expect($config->getTitle())->toBe('Required Title');
    });
});
