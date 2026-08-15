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

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final readonly class GalleryZipCreator
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private Filesystem $fs,
    ) {
    }

    /**
     * @param list<GalleryImage> $images
     *
     * @return string path to the created ZIP file
     */
    public function create(array $images): string
    {
        $temporaryFile = $this->fs->tempnam(sys_get_temp_dir(), 'folder-gallery-');

        $zip = new \ZipArchive();

        if (true !== $zip->open($temporaryFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            @$this->fs->remove($temporaryFile);

            throw new \RuntimeException('Could not create ZIP archive.');
        }

        foreach ($images as $image) {
            $path = $this->projectDir.'/'.$image->path;

            if (!is_file($path)) {
                $zip->close();
                @$this->fs->remove($temporaryFile);

                throw new \RuntimeException(\sprintf('Gallery image "%s" does not exist.', $image->path));
            }

            if (false === $zip->addFile($path, $image->filename)) {
                $zip->close();
                @$this->fs->remove($temporaryFile);

                throw new \RuntimeException(\sprintf('Could not add file "%s" to ZIP archive.', $image->path));
            }

            $zip->setCompressionName($image->filename, \ZipArchive::CM_STORE);
        }

        if (false === $zip->close()) {
            @$this->fs->remove($temporaryFile);

            throw new \RuntimeException('Could not finalize ZIP archive.');
        }

        return $temporaryFile;
    }
}
