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
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryRoot;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadAvailabilityChecker;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipImageCollector;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(GalleryDownloadAvailabilityChecker::class)]
final class GalleryDownloadAvailabilityCheckerTest extends ContaoTestCase
{
    public function testIsAvailableWhenGalleryContainsImages(): void
    {
        $folder = $this->createFolder([$this->createImage()]);
        $overview = $this->createOverview($folder);
        $page = $this->createStub(PageModel::class);

        $dispatchedEvent = null;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (GalleryDownloadActionEvent $event) use (&$dispatchedEvent): GalleryDownloadActionEvent {
                    $dispatchedEvent = $event;

                    return $event;
                },
            )
        ;

        $checker = new GalleryDownloadAvailabilityChecker(new GalleryZipImageCollector(), $eventDispatcher);

        $this->assertTrue($checker->isAvailable($overview, $folder, $page));
        $this->assertInstanceOf(GalleryDownloadActionEvent::class, $dispatchedEvent);
        $this->assertSame($overview, $dispatchedEvent->overview);
        $this->assertSame($folder, $dispatchedEvent->folder);
        $this->assertSame($page, $dispatchedEvent->page);
    }

    public function testIsNotAvailableWhenGalleryContainsNoImages(): void
    {
        $folder = $this->createFolder([]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $checker = new GalleryDownloadAvailabilityChecker(new GalleryZipImageCollector(), $eventDispatcher);

        $this->assertFalse($checker->isAvailable($this->createOverview($folder), $folder, $this->createStub(PageModel::class)));
    }

    public function testIsNotAvailableWhenEventListenerDisablesDownload(): void
    {
        $folder = $this->createFolder([$this->createImage()]);

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(
                static function (GalleryDownloadActionEvent $event): GalleryDownloadActionEvent {
                    $event->disable();

                    return $event;
                },
            )
        ;

        $checker = new GalleryDownloadAvailabilityChecker(new GalleryZipImageCollector(), $eventDispatcher);

        $this->assertFalse($checker->isAvailable($this->createOverview($folder), $folder, $this->createStub(PageModel::class)));
    }

    private function createImage(): GalleryImage
    {
        return new GalleryImage(
            uuid: '00000000-0000-0000-0000-000000000001',
            path: 'files/gallery/image.jpg',
            filename: 'image.jpg',
            isCover: false,
        );
    }

    /**
     * @param list<GalleryImage> $images
     */
    private function createFolder(array $images): GalleryFolder
    {
        return new GalleryFolder(
            slug: 'gallery',
            title: 'Gallery',
            filesystemDirectory: 'gallery',
            trail: ['gallery'],
            metadata: new GalleryMetadata(),
            images: $images,
        );
    }

    private function createOverview(GalleryFolder $folder): GalleryOverview
    {
        return new GalleryOverview(
            root: new GalleryRoot('gallery', 42, '/root/filesystem'),
            folders: [$folder],
            folderIndex: [$folder->getPath() => $folder],
        );
    }
}
