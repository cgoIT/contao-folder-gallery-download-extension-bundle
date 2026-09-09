<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

final readonly class FilesCallbacks
{
    /**
     * @param array<string> $validImageExtensions
     */
    public function __construct(
        #[Autowire('%contao.image.valid_extensions%')]
        private array $validImageExtensions,
    ) {
    }

    #[AsCallback(table: 'tl_files', target: 'config.onpalette')]
    public function addExcludeFromZipField(string $palette, DataContainer $dc): string
    {
        if (!\in_array(Path::getExtension((string) $dc->id, true), $this->validImageExtensions, true)) {
            return $palette;
        }

        return PaletteManipulator::create()
            ->addField('folderGalleryExcludeFromZip', 'importantPartHeight', PaletteManipulator::POSITION_AFTER)
            ->applyToString($palette)
        ;
    }
}
