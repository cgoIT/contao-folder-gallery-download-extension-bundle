<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Controller;

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryBundle\Provider\GalleryProviderInterface;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadFilenameGenerator;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipCreator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/_folder-gallery/download/{moduleId}/{path}',
    name: self::DOWNLOAD_ROUTE_NAME,
    requirements: [
        'moduleId' => '\d+',
        'path' => '.+',
    ],
    defaults: [
        '_scope' => 'frontend',
    ],
    methods: ['GET'],
)]
final readonly class GalleryDownloadController
{
    public const string DOWNLOAD_ROUTE_NAME = 'cgoit_contao_folder_gallery_download_extension';

    public function __construct(
        private GalleryProviderInterface $folderProvider,
        private GalleryZipCreator $galleryZipCreator,
        private GalleryDownloadFilenameGenerator $filenameGenerator,
    ) {
    }

    public function __invoke(int $moduleId, string $path): Response
    {
        $galleryFolder = $this->folderProvider->findFolderByModuleIdAndPath($moduleId, $path);

        if (null === $galleryFolder) {
            throw new NotFoundHttpException();
        }

        $images = $this->getImages($galleryFolder);

        $zipFile = $this->galleryZipCreator->create($images);
        clearstatcache(true, $zipFile);

        $filename = $this->filenameGenerator->generate($galleryFolder, $path);

        $response = new BinaryFileResponse($zipFile);
        $response->headers->set(
            'Content-Length',
            (string) filesize($zipFile),
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        $response->deleteFileAfterSend();

        return $response;
    }

    /**
     * @return list<GalleryImage>
     */
    private function getImages(GalleryFolder $folder): array
    {
        if (!$folder->metadata->hideCoverInGallery) {
            return $folder->images;
        }

        return array_filter($folder->images, static fn (GalleryImage $image) => !$image->isCover);
    }
}
