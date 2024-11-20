# Changelog

## 2.0.3 - 20th November 2024
- Fix relative image for EPUB with custom working directory
- Update readme for frontmatter options
- Welcome PHP 8.4

## 2.0.2 - 14th September 2024
- Restyle init command with SymfonyStyle
- The init command creates automatically the working directory

## 2.0.1 - 13th September 2024
- Check if configuration exists
- Update stubs directory with sample images
- Update the version for `--version`
- update highlight js to 9.18.1 (latest version for 9 major rel)


## 2.0.0 - 13th September 2024
- Upgrading to Pest3 and PHPunit 11(PHP Testing)
- Dropping support for PHP 8.1
- Updating Rector rules for PHP >= 8.2

## 1.2.0 - 10th September 2024
- Embed images for EPUB
- Fix loading image via relative path
- Updating dependencies

## 1.1.2 - 3rd September 2024
- Adding Markdown custom extension for managing attributes (like CSS class)
- Updating dependencies

## 1.1.1 - 24th Aug 2024
- Updating dependencies
- Updating some styles/fonts for Headings


## 1.1.0 - 26th May 2024
- Adding `md_file_list` for selecting a subset of Markdown file in the "content" directory, for ebook creation
- Updating dependencies (Illuminate 11)
- Updating Rector 1
- Supporting an additional blockquote syntax like `[!NOTE]` and `[!WARNING]`

## 1.0.12 - 4th February 2024
- Exporting HTML

## 1.0.11 - 3rd February 2024
- Adding PHPstan for static code analysis
- Adding ext-* dependencies in composer.json
- Adding function for building path
- Adding tests with PestPHP
- Improving GitHub Actions Workflows


## 1.0.10 - 24th January 2024
- Fixing and updating styles for Aside blok

## 1.0.9 - 23th January 2024
- Adding Aside block rendering
- Updating rector 0.19

## 1.0.8 - 2nd January 2024
- Fixing and updating the Sample PDF generation

## 1.0.7 - 31th December 2023
- Fixing Table of Content
- SetList::CODING_STYLE

## 1.0.6 - 30th December 2023
- Updating the Sample book
- Using WebP for the cover image
- Updating Highlightjs CSS

## 1.0.5 - 30th December 2023

- Adding the option `-d` for the `init` command to initialize the ebook in a different directory

## 1.0.4 - 28th December 2023

- Adding the option for customizing the working path (the directory with the assets folder)
- Adding the option for customizing the content path (the directory where you have your Markdown files)
- Now you can organize your markdown files in subfolders
- Eliminating most of the warnings during the EPUB generation process (thanks to the `epubcheck` tool)
- Refactoring the configuration class


## 1.0.3 - 21th December 2023
- Creating the export directory if not exist for EPUB creation
- Improving metadata for EPUB
- Table of Contents or EPUB

## 1.0.2 - 21th December 2023
- Setting the content directory
- Refactoring common code EPUB and PDF build
- Introducing RectorPHP


## 1.0.1 - 21th December 2023
- Welcome to the EPUB generation

## 1.0.0 - 17th December 2023

- upgrade and check with PHP 8.2 and PHP 8.3
- update support for Symfony 7 components
- upgrade code using the new renderer of CommonMark
- upgrade GitHub Actions workflow
- using Pint with PSR12
- added configuration for cover image (instead of using hard-coded cover.jpg, you can specify a new file name and format, for example, my-cover.png)
- added the header config for the CSS style for the page header
- added the front matter capabilities (the title front matter option will be used for the page header).
