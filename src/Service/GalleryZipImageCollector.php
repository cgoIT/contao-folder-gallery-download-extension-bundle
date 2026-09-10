<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service;

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Repository\GalleryZipExclusionRepository;
use Symfony\Component\Filesystem\Path;

final readonly class GalleryZipImageCollector
{
    public function __construct(private GalleryZipExclusionRepository $exclusionRepository)
    {
    }

    /**
     * Recursively collects all images of a gallery folder and its subfolders, keyed by
     * their path inside the ZIP archive (mirroring the filesystem folder structure), with
     * unpublished (sub-)folders, hidden cover images and individually excluded images removed.
     *
     * @return array<string, GalleryImage>
     */
    public function collect(GalleryFolder $folder): array
    {
        $images = $this->collectFromFolder($folder, '');

        if ([] === $images) {
            return [];
        }

        $excludedUuids = $this->exclusionRepository->findExcludedUuids($folder->filesystemDirectory);

        if ([] === $excludedUuids) {
            return $images;
        }

        $excludedUuids = array_flip($excludedUuids);

        return array_filter(
            $images,
            static fn (GalleryImage $image): bool => !isset($excludedUuids[$image->uuid]),
        );
    }

    /**
     * @return array<string, GalleryImage>
     */
    private function collectFromFolder(GalleryFolder $folder, string $relativePath): array
    {
        if (!$folder->metadata->isPublished()) {
            return [];
        }

        $images = [];

        foreach ($this->getVisibleImages($folder) as $image) {
            $images[Path::join($relativePath, $image->filename)] = $image;
        }

        foreach ($folder->folders as $subFolder) {
            $images += $this->collectFromFolder(
                $subFolder,
                Path::join($relativePath, basename($subFolder->filesystemDirectory)),
            );
        }

        return $images;
    }

    /**
     * @return list<GalleryImage>
     */
    private function getVisibleImages(GalleryFolder $folder): array
    {
        if (!$folder->metadata->hideCoverInGallery) {
            return $folder->images;
        }

        return array_values(array_filter(
            $folder->images,
            static fn (GalleryImage $image): bool => !$image->isCover,
        ));
    }
}
