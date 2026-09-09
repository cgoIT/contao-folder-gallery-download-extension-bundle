<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Tests\EventListener\DataContainer;

use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\EventListener\DataContainer\FilesCallbacks;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilesCallbacks::class)]
#[UsesClass(PaletteManipulator::class)]
final class FilesCallbacksTest extends TestCase
{
    public function testAddsFieldForImageFiles(): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc
            ->method('__get')
            ->willReturn('files/gallery/image.jpg')
        ;

        $callbacks = new FilesCallbacks(['jpg', 'png']);

        $palette = $callbacks->addExcludeFromZipField('name,importantPartX,importantPartHeight', $dc);

        $this->assertStringContainsString('folderGalleryExcludeFromZip', $palette);
    }

    public function testDoesNotAddFieldForNonImageFiles(): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc
            ->method('__get')
            ->willReturn('files/gallery/document.pdf')
        ;

        $callbacks = new FilesCallbacks(['jpg', 'png']);

        $palette = 'name,importantPartX,importantPartHeight';

        $this->assertSame($palette, $callbacks->addExcludeFromZipField($palette, $dc));
    }
}
