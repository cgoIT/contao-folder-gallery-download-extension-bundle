<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CgoitFolderGalleryDownloadExtensionBundle extends Bundle
{
    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
