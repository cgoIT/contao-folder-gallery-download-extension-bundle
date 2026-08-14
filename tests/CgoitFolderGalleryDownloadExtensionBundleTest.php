<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryBundle\Tests;

use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\CgoitFolderGalleryDownloadExtensionBundle;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CgoitFolderGalleryDownloadExtensionBundle::class)]
final class CgoitFolderGalleryDownloadExtensionBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $container = $this->createStub(ContainerBuilder::class);
        $bundle = new CgoitFolderGalleryDownloadExtensionBundle();
        $bundle->build($container);

        $this->addToAssertionCount(1);
    }
}
