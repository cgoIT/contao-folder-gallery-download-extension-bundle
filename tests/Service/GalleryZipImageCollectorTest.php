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
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryMetadata;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipImageCollector;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GalleryZipImageCollector::class)]
final class GalleryZipImageCollectorTest extends ContaoTestCase
{
    public function testCollectsImagesFromRootAndSubfolders(): void
    {
        $rootImage = $this->createImage('00000000-0000-0000-0000-000000000001', 'image-a.jpg');
        $subImage = $this->createImage('00000000-0000-0000-0000-000000000002', 'room-1.jpg');

        $subFolder = $this->createFolder('/gallery/rooms', images: [$subImage]);
        $folder = $this->createFolder('/gallery', images: [$rootImage], folders: [$subFolder]);

        $collector = new GalleryZipImageCollector();

        $images = $collector->collect($folder);

        $this->assertSame(['image-a.jpg', 'rooms/room-1.jpg'], array_keys($images));
    }

    public function testSkipsImagesOfUnpublishedSubfolder(): void
    {
        $rootImage = $this->createImage('00000000-0000-0000-0000-000000000001', 'image-a.jpg');
        $subImage = $this->createImage('00000000-0000-0000-0000-000000000002', 'room-1.jpg');

        $subFolder = $this->createFolder(
            '/gallery/rooms',
            images: [$subImage],
            metadata: new GalleryMetadata(publishedUntil: new \DateTimeImmutable('2000-01-01')),
        );
        $folder = $this->createFolder('/gallery', images: [$rootImage], folders: [$subFolder]);

        $collector = new GalleryZipImageCollector();

        $images = $collector->collect($folder);

        $this->assertSame(['image-a.jpg'], array_keys($images));
    }

    public function testHidesCoverImageOnlyForFoldersWithHideCoverInGallery(): void
    {
        $rootCover = $this->createImage('00000000-0000-0000-0000-000000000001', 'cover.jpg', isCover: true);
        $subCover = $this->createImage('00000000-0000-0000-0000-000000000002', 'sub-cover.jpg', isCover: true);

        $subFolder = $this->createFolder('/gallery/rooms', images: [$subCover]);
        $folder = $this->createFolder(
            '/gallery',
            images: [$rootCover],
            folders: [$subFolder],
            metadata: new GalleryMetadata(hideCoverInGallery: true),
        );

        $collector = new GalleryZipImageCollector();

        $images = $collector->collect($folder);

        $this->assertSame(['rooms/sub-cover.jpg'], array_keys($images));
    }

    public function testReturnsEmptyArrayWhenRootFolderIsUnpublished(): void
    {
        $folder = $this->createFolder(
            '/gallery',
            images: [$this->createImage('00000000-0000-0000-0000-000000000001', 'image-a.jpg')],
            metadata: new GalleryMetadata(publishedUntil: new \DateTimeImmutable('2000-01-01')),
        );

        $collector = new GalleryZipImageCollector();

        $this->assertSame([], $collector->collect($folder));
    }

    private function createImage(string $uuid, string $filename, bool $isCover = false): GalleryImage
    {
        return new GalleryImage(
            uuid: $uuid,
            path: 'files/'.$filename,
            filename: $filename,
            isCover: $isCover,
        );
    }

    /**
     * @param list<GalleryImage>  $images
     * @param list<GalleryFolder> $folders
     */
    private function createFolder(string $filesystemDirectory, array $images = [], array $folders = [], GalleryMetadata|null $metadata = null): GalleryFolder
    {
        $name = basename($filesystemDirectory);

        return new GalleryFolder(
            slug: $name,
            title: $name,
            filesystemDirectory: $filesystemDirectory,
            trail: [$name],
            metadata: $metadata ?? new GalleryMetadata(),
            folders: $folders,
            images: $images,
        );
    }
}
