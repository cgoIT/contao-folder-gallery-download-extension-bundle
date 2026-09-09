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

use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipExclusionRepository;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GalleryZipExclusionRepository::class)]
final class GalleryZipExclusionRepositoryTest extends ContaoTestCase
{
    public function testReturnsUuidsOfExcludedFilesInsideFolderTree(): void
    {
        $uuidA = '00000000-0000-0000-0000-000000000001';
        $uuidB = '00000000-0000-0000-0000-000000000002';

        $fileA = $this->createClassWithPropertiesStub(FilesModel::class, [
            'uuid' => StringUtil::uuidToBin($uuidA),
        ]);
        $fileB = $this->createClassWithPropertiesStub(FilesModel::class, [
            'uuid' => StringUtil::uuidToBin($uuidB),
        ]);

        $adapter = $this->createAdapterMock(['findBy']);
        $adapter
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['folderGalleryExcludeFromZip = 1 AND path LIKE ?'],
                ['files/hotels/achim-bremen/%'],
            )
            ->willReturn([$fileA, $fileB])
        ;

        $framework = $this->createContaoFrameworkStub([FilesModel::class => $adapter]);

        $repository = new GalleryZipExclusionRepository($framework);

        $this->assertSame([$uuidA, $uuidB], $repository->findExcludedUuids('files/hotels/achim-bremen'));
    }

    public function testEscapesLikeWildcardsInFolderPath(): void
    {
        $adapter = $this->createAdapterMock(['findBy']);
        $adapter
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['folderGalleryExcludeFromZip = 1 AND path LIKE ?'],
                ['files/hotel\\_50\\%off/%'],
            )
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([FilesModel::class => $adapter]);

        $repository = new GalleryZipExclusionRepository($framework);

        $repository->findExcludedUuids('files/hotel_50%off');
    }

    public function testReturnsEmptyArrayWhenNoFileIsExcluded(): void
    {
        $adapter = $this->createConfiguredAdapterStub(['findBy' => null]);
        $framework = $this->createContaoFrameworkStub([FilesModel::class => $adapter]);

        $repository = new GalleryZipExclusionRepository($framework);

        $this->assertSame([], $repository->findExcludedUuids('files/hotels/achim-bremen'));
    }
}
