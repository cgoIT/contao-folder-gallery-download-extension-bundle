<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Controller;

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryBundle\Provider\GalleryProviderInterface;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadAvailabilityChecker;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadFilenameGenerator;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipCreator;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipImageCollector;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The route parameter is deliberately named "pageModel": Contao's core request
 * listeners resolve it to the page model and deny access if the page is protected
 * and the visitor is not allowed to see it (or if the site is in maintenance mode).
 */
#[Route(
    '/_folder-gallery/download/{moduleId}/{pageModel}/{path}',
    name: self::DOWNLOAD_ROUTE_NAME,
    requirements: [
        'moduleId' => '\d+',
        'pageModel' => '\d+',
        'path' => '.+',
    ],
    defaults: [
        '_scope' => 'frontend',
    ],
    methods: ['GET'],
)]
final readonly class GalleryDownloadController
{
    public const string DOWNLOAD_ROUTE_NAME = 'cgoit_contao_folder_gallery_download_extension';

    public function __construct(
        private GalleryProviderInterface $folderProvider,
        private GalleryDownloadAvailabilityChecker $availabilityChecker,
        private GalleryZipImageCollector $imageCollector,
        private GalleryZipCreator $galleryZipCreator,
        private GalleryDownloadFilenameGenerator $filenameGenerator,
        private UriSigner $uriSigner,
        private TokenChecker $tokenChecker,
    ) {
    }

    public function __invoke(Request $request, int $moduleId, string $path): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw new NotFoundHttpException();
        }

        $page = $request->attributes->get('pageModel');

        if (!$page instanceof PageModel || !$this->isPagePublished($page)) {
            throw new NotFoundHttpException();
        }

        $overview = $this->findOverviewByModuleId($moduleId);
        $galleryFolder = $overview?->findFolderByPath($path);

        if (null === $overview || null === $galleryFolder) {
            throw new NotFoundHttpException();
        }

        if (!$this->availabilityChecker->isAvailable($overview, $galleryFolder, $page)) {
            throw new NotFoundHttpException();
        }

        $zipFile = $this->galleryZipCreator->create($this->imageCollector->collect($galleryFolder));
        clearstatcache(true, $zipFile);

        $filename = $this->filenameGenerator->generate($galleryFolder, $path);

        $response = new BinaryFileResponse($zipFile);
        $response->headers->set(
            'Content-Length',
            (string) filesize($zipFile),
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        $response->deleteFileAfterSend();

        return $response;
    }

    private function isPagePublished(PageModel $page): bool
    {
        if ($this->tokenChecker->isPreviewMode()) {
            return true;
        }

        // The page details (including isPublic and rootIsPublic) have already been
        // loaded by Contao's PageAccessListener
        return $page->isPublic && $page->rootIsPublic;
    }

    private function findOverviewByModuleId(int $moduleId): GalleryOverview|null
    {
        foreach ($this->folderProvider->findAllOverviews() as $overview) {
            if ($overview->getModuleId() === $moduleId) {
                return $overview;
            }
        }

        return null;
    }
}
