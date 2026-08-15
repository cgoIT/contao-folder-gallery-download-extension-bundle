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

final class GalleryDownloadFilenameGenerator
{
    public function generate(GalleryFolder $folder, string $path): string
    {
        $name = trim($folder->metadata->title ?? '');

        if ('' === $name) {
            $name = basename(rtrim($path, '/'));
        }

        // Replace characters that are not valid in filenames on common platforms.
        $name = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]+/u', ' - ', $name) ?? $name;

        // Collapse whitespace and repeated separators.
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s*-\s*/u', ' - ', $name) ?? $name;

        // Trailing dots and spaces are not valid on Windows.
        $name = rtrim($name, ' .');

        // Avoid Windows reserved device names.
        if (preg_match('/^(CON|PRN|AUX|NUL|COM[0-9]|LPT[0-9])(?:\..*)?$/iu', $name)) {
            $name = '_'.$name;
        }

        if ('' === $name) {
            $name = 'gallery';
        }

        return $name.'.zip';
    }
}
