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

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipCreator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(GalleryZipCreator::class)]
final class GalleryZipCreatorTest extends TestCase
{
    private string $projectDir;

    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->projectDir = sys_get_temp_dir().'/folder-gallery-zip-creator-test-'.uniqid();

        $this->fs->mkdir($this->projectDir.'/files/gallery/rooms');
        $this->fs->dumpFile($this->projectDir.'/files/gallery/image-a.jpg', 'root-image');
        $this->fs->dumpFile($this->projectDir.'/files/gallery/rooms/room-1.jpg', 'sub-image');
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->projectDir);
    }

    public function testCreatesZipArchiveMirroringFolderStructure(): void
    {
        $creator = new GalleryZipCreator($this->projectDir, $this->fs);

        $zipFile = $creator->create([
            'image-a.jpg' => new GalleryImage(
                uuid: '00000000-0000-0000-0000-000000000001',
                path: 'files/gallery/image-a.jpg',
                filename: 'image-a.jpg',
                isCover: false,
            ),
            'rooms/room-1.jpg' => new GalleryImage(
                uuid: '00000000-0000-0000-0000-000000000002',
                path: 'files/gallery/rooms/room-1.jpg',
                filename: 'room-1.jpg',
                isCover: false,
            ),
        ]);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipFile));

        $this->assertSame(2, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('image-a.jpg'));
        $this->assertNotFalse($zip->locateName('rooms/room-1.jpg'));

        $zip->close();
        $this->fs->remove($zipFile);
    }

    public function testThrowsExceptionWhenImageFileIsMissing(): void
    {
        $creator = new GalleryZipCreator($this->projectDir, $this->fs);

        $this->expectException(\RuntimeException::class);

        $creator->create([
            'missing.jpg' => new GalleryImage(
                uuid: '00000000-0000-0000-0000-000000000001',
                path: 'files/gallery/missing.jpg',
                filename: 'missing.jpg',
                isCover: false,
            ),
        ]);
    }
}
