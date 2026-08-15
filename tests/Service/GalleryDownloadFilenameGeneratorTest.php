<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Tests\Service;

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryMetadata;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadFilenameGenerator;
use PHPUnit\Framework\TestCase;

final class GalleryDownloadFilenameGeneratorTest extends TestCase
{
    public function testCreatesFilenameFromGalleryTitle(): void
    {
        $folder = $this->createGalleryFolder('Stadtfest: Freitag/Samstag');

        $generator = new GalleryDownloadFilenameGenerator();

        $this->assertSame(
            'Stadtfest - Freitag - Samstag.zip',
            $generator->generate($folder, '2026/freitag'),
        );
    }

    public function testUsesFolderNameWhenGalleryTitleIsEmpty(): void
    {
        $folder = $this->createGalleryFolder('');

        $generator = new GalleryDownloadFilenameGenerator();

        $this->assertSame(
            'freitag.zip',
            $generator->generate($folder, '2026/freitag'),
        );
    }

    private function createGalleryFolder(string $title): GalleryFolder
    {
        return new GalleryFolder(
            slug: $title,
            title: $title,
            filesystemDirectory: '/folder',
            trail: [$title],
            metadata: new GalleryMetadata(
                title: $title,
                hideCoverInGallery: false,
            ),
            folders: [],
            images: [],
        );
    }
}
