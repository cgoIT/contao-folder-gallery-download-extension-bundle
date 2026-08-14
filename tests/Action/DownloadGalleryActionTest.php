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

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryMetadata;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryRoot;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Action\DownloadGalleryAction;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DownloadGalleryActionTest extends TestCase
{
    public function testCreatesAction(): void
    {
        $folder = new GalleryFolder(
            slug: '/folder',
            title: 'Folder',
            filesystemDirectory: 'folder',
            trail: ['Folder'],
            metadata: new GalleryMetadata(),
            folders: [],
            images: [],
        );

        $overview = new GalleryOverview(
            root: new GalleryRoot('gallery', 42, '/root/filesystem'),
            folders: [$folder],
            folderIndex: ['folder' => $folder],
        );

        $page = $this->createStub(PageModel::class);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                'cgoit_contao_folder_gallery_download_extension',
                [
                    'moduleId' => 42,
                    'path' => 'Folder',
                ],
            )
            ->willReturn('/gallery/download/42/Folder')
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

        $action = new DownloadGalleryAction(
            $urlGenerator,
            $translator,
        );

        $result = $action->createAction(
            $overview,
            $folder,
            $page,
        );

        $this->assertSame('Galerie herunterladen', $result->label);
        $this->assertSame('/gallery/download/42/Folder', $result->url);
        $this->assertSame('Alle Bilder der Galerie als ZIP-Datei herunterladen', $result->title);
        $this->assertSame('download', $result->icon);
    }
}
