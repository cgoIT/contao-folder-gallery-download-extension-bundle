<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Tests\Action;

use Cgoit\ContaoFolderGalleryBundle\Action\GalleryContentAction;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryMetadata;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryRoot;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Action\DownloadGalleryAction;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadAvailabilityChecker;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipImageCollector;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DownloadGalleryActionTest extends ContaoTestCase
{
    public function testCreatesActionWithSignedUrl(): void
    {
        $folder = $this->createFolder('folder', images: [$this->createImage('image.jpg')]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                'cgoit_contao_folder_gallery_download_extension',
                [
                    'moduleId' => 42,
                    'pageModel' => 7,
                    'path' => 'Folder',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.com/_folder-gallery/download/42/7/Folder')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [
                    'download_gallery.action.label',
                    [],
                    'cgoit_contao_folder_gallery_download_extension',
                    null,
                    'Galerie herunterladen',
                ],
                [
                    'download_gallery.action.title',
                    [],
                    'cgoit_contao_folder_gallery_download_extension',
                    null,
                    'Alle Bilder der Galerie als ZIP-Datei herunterladen',
                ],
            ])
        ;

        $uriSigner = new UriSigner('secret');

        $action = new DownloadGalleryAction(
            $urlGenerator,
            $uriSigner,
            $translator,
            $this->createAvailabilityChecker(),
        );

        $result = $action->createAction(
            $this->createOverview($folder),
            $folder,
            $this->mockPage(),
        );

        $this->assertInstanceOf(GalleryContentAction::class, $result);
        $this->assertSame('Galerie herunterladen', $result->label);
        $this->assertStringStartsWith('https://example.com/_folder-gallery/download/42/7/Folder?_hash=', $result->url);
        $this->assertTrue($uriSigner->check($result->url));
        $this->assertSame('Alle Bilder der Galerie als ZIP-Datei herunterladen', $result->title);
        $this->assertSame('download', $result->type);
    }

    public function testCreatesActionWhenImagesExistOnlyInSubfolders(): void
    {
        $subFolder = $this->createFolder('rooms', images: [$this->createImage('room-1.jpg')]);
        $folder = $this->createFolder('folder', folders: [$subFolder]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturn('https://example.com/_folder-gallery/download/42/7/Folder')
        ;

        $action = new DownloadGalleryAction(
            $urlGenerator,
            new UriSigner('secret'),
            $this->createStub(TranslatorInterface::class),
            $this->createAvailabilityChecker(),
        );

        $result = $action->createAction(
            $this->createOverview($folder),
            $folder,
            $this->mockPage(),
        );

        $this->assertInstanceOf(GalleryContentAction::class, $result);
    }

    public function testReturnsNullWhenGalleryContainsNoDownloadableImages(): void
    {
        $unpublishedSubFolder = $this->createFolder(
            'rooms',
            images: [$this->createImage('room-1.jpg')],
            metadata: new GalleryMetadata(publishedUntil: new \DateTimeImmutable('2000-01-01')),
        );
        $emptySubFolder = $this->createFolder('empty');
        $folder = $this->createFolder('folder', folders: [$unpublishedSubFolder, $emptySubFolder]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $action = new DownloadGalleryAction(
            $urlGenerator,
            new UriSigner('secret'),
            $this->createStub(TranslatorInterface::class),
            new GalleryDownloadAvailabilityChecker(new GalleryZipImageCollector(), $eventDispatcher),
        );

        $result = $action->createAction(
            $this->createOverview($folder),
            $folder,
            $this->mockPage(),
        );

        $this->assertNull($result);
    }

    public function testReturnsNullWhenEventListenerDisablesDownload(): void
    {
        $folder = $this->createFolder('folder', images: [$this->createImage('image.jpg')]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $action = new DownloadGalleryAction(
            $urlGenerator,
            new UriSigner('secret'),
            $this->createStub(TranslatorInterface::class),
            $this->createAvailabilityChecker(enabled: false),
        );

        $result = $action->createAction(
            $this->createOverview($folder),
            $folder,
            $this->mockPage(),
        );

        $this->assertNull($result);
    }

    private function createAvailabilityChecker(bool $enabled = true): GalleryDownloadAvailabilityChecker
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(
                static function (GalleryDownloadActionEvent $event) use ($enabled): GalleryDownloadActionEvent {
                    if (!$enabled) {
                        $event->disable();
                    }

                    return $event;
                },
            )
        ;

        return new GalleryDownloadAvailabilityChecker(new GalleryZipImageCollector(), $eventDispatcher);
    }

    private function mockPage(): PageModel
    {
        return $this->createClassWithPropertiesStub(PageModel::class, ['id' => 7]);
    }

    private function createImage(string $filename): GalleryImage
    {
        return new GalleryImage(
            uuid: '00000000-0000-0000-0000-000000000001',
            path: 'files/'.$filename,
            filename: $filename,
            isCover: false,
        );
    }

    /**
     * @param list<GalleryImage>  $images
     * @param list<GalleryFolder> $folders
     */
    private function createFolder(string $name, array $images = [], array $folders = [], GalleryMetadata|null $metadata = null): GalleryFolder
    {
        return new GalleryFolder(
            slug: '/'.$name,
            title: ucfirst($name),
            filesystemDirectory: $name,
            trail: [ucfirst($name)],
            metadata: $metadata ?? new GalleryMetadata(),
            folders: $folders,
            images: $images,
        );
    }

    private function createOverview(GalleryFolder $folder): GalleryOverview
    {
        return new GalleryOverview(
            root: new GalleryRoot('gallery', 42, '/root/filesystem'),
            folders: [$folder],
            folderIndex: [$folder->filesystemDirectory => $folder],
        );
    }
}
