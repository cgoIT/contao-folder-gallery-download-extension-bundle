<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Action;

use Cgoit\ContaoFolderGalleryBundle\Action\GalleryContentAction;
use Cgoit\ContaoFolderGalleryBundle\Action\GalleryContentActionInterface;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Controller\GalleryDownloadController;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadAvailabilityChecker;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DownloadGalleryAction implements GalleryContentActionInterface
{
    public const string ACTION_TYPE = 'download';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner,
        private TranslatorInterface $translator,
        private GalleryDownloadAvailabilityChecker $availabilityChecker,
    ) {
    }

    public function createAction(GalleryOverview $overview, GalleryFolder $folder, PageModel $page): GalleryContentAction|null
    {
        if (!$this->availabilityChecker->isAvailable($overview, $folder, $page)) {
            return null;
        }

        // The URL is signed so that the page ID, on which the page access check and the
        // event listeners in the download controller rely, cannot be tampered with
        $url = $this->uriSigner->sign(
            $this->urlGenerator->generate(
                GalleryDownloadController::DOWNLOAD_ROUTE_NAME,
                [
                    'moduleId' => $overview->getModuleId(),
                    'pageModel' => (int) $page->id,
                    'path' => $folder->getPath(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        );

        return new GalleryContentAction(
            type: self::ACTION_TYPE,
            label: $this->translator->trans(
                'download_gallery.action.label',
                [],
                'cgoit_contao_folder_gallery_download_extension',
            ),
            url: $url,
            title: $this->translator->trans(
                'download_gallery.action.title',
                [],
                'cgoit_contao_folder_gallery_download_extension',
            ),
        );
    }
}
