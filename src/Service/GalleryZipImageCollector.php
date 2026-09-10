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
use Symfony\Component\Filesystem\Path;

final readonly class GalleryZipImageCollector
{
    /**
     * Recursively collects all images of a gallery folder and its subfolders, keyed by
     * their path inside the ZIP archive (mirroring the filesystem folder structure), with
     * unpublished (sub-)folders and hidden cover images removed. Images hidden via the
     * hideInGallery flag never reach $folder->images in the first place, since the base
     * bundle already filters them out when loading the folder.
     *
     * @return array<string, GalleryImage>
     */
    public function collect(GalleryFolder $folder): array
    {
        return $this->collectFromFolder($folder, '');
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
