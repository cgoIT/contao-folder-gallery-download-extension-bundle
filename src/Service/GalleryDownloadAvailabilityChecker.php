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
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent;
use Contao\PageModel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Decides whether a gallery may be downloaded. Used both when rendering the download
 * action and when serving the download itself, so that hiding the action via the
 * GalleryDownloadActionEvent also blocks the download endpoint.
 */
final readonly class GalleryDownloadAvailabilityChecker
{
    public function __construct(
        private GalleryZipImageCollector $imageCollector,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function isAvailable(GalleryOverview $overview, GalleryFolder $folder, PageModel $page): bool
    {
        // Don't offer a download that would result in an empty ZIP archive
        if ([] === $this->imageCollector->collect($folder)) {
            return false;
        }

        $event = new GalleryDownloadActionEvent($overview, $folder, $page);

        $this->eventDispatcher->dispatch($event);

        return $event->isEnabled();
    }
}
