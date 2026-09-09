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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\StringUtil;

final readonly class GalleryZipExclusionRepository
{
    public function __construct(private ContaoFramework $framework)
    {
    }

    /**
     * Returns the UUIDs of all files inside the given folder (recursively) that are currently
     * flagged to be excluded from the gallery ZIP download.
     *
     * @return list<string>
     */
    public function findExcludedUuids(string $folderPath): array
    {
        $files = $this->framework
            ->getAdapter(FilesModel::class)
            ->findBy(
                ['folderGalleryExcludeFromZip = 1 AND path LIKE ?'],
                [addcslashes($folderPath, '%_').'/%'],
            )
        ;

        if (null === $files) {
            return [];
        }

        $uuids = [];

        foreach ($files as $file) {
            $uuids[] = StringUtil::binToUuid($file->uuid);
        }

        return $uuids;
    }
}
