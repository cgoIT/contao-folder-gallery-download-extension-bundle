<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Tests\Controller;

use Cgoit\ContaoFolderGalleryBundle\Model\GalleryFolder;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryImage;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryMetadata;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryOverview;
use Cgoit\ContaoFolderGalleryBundle\Model\GalleryRoot;
use Cgoit\ContaoFolderGalleryBundle\Provider\GalleryProviderInterface;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Controller\GalleryDownloadController;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Event\GalleryDownloadActionEvent;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadAvailabilityChecker;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryDownloadFilenameGenerator;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipCreator;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Service\GalleryZipImageCollector;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(GalleryDownloadController::class)]
final class GalleryDownloadControllerTest extends ContaoTestCase
{
    private const string DOWNLOAD_URL = 'https://example.com/_folder-gallery/download/42/7/gallery';

    private string $projectDir;

    private Filesystem $fs;

    private UriSigner $uriSigner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem();
        $this->projectDir = sys_get_temp_dir().'/folder-gallery-download-controller-test-'.uniqid();
        $this->uriSigner = new UriSigner('secret');

        $this->fs->dumpFile($this->projectDir.'/files/gallery/image.jpg', 'image');
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->projectDir);

        parent::tearDown();
    }

    public function testDownloadsGallery(): void
    {
        $response = $this->createController()(
            $this->createRequest($this->mockPage()),
            42,
            'gallery',
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('attachment; filename=gallery.zip', $response->headers->get('Content-Disposition'));

        $zipFile = $response->getFile()->getPathname();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipFile));
        $this->assertNotFalse($zip->locateName('image.jpg'));
        $zip->close();

        $this->fs->remove($zipFile);
    }

    public function testThrowsNotFoundForUnsignedUrl(): void
    {
        $request = Request::create(self::DOWNLOAD_URL);
        $request->attributes->set('pageModel', $this->mockPage());

        $this->expectException(NotFoundHttpException::class);

        $this->createController()($request, 42, 'gallery');
    }

    public function testThrowsNotFoundForTamperedUrl(): void
    {
        $signedUrl = $this->uriSigner->sign(self::DOWNLOAD_URL);

        $request = Request::create(str_replace('/42/7/', '/42/8/', $signedUrl));
        $request->attributes->set('pageModel', $this->mockPage());

        $this->expectException(NotFoundHttpException::class);

        $this->createController()($request, 42, 'gallery');
    }

    public function testThrowsNotFoundIfPageDoesNotExist(): void
    {
        // Contao's PageAccessListener leaves the raw ID in place if the page is not found
        $request = Request::create($this->uriSigner->sign(self::DOWNLOAD_URL));
        $request->attributes->set('pageModel', '7');

        $this->expectException(NotFoundHttpException::class);

        $this->createController()($request, 42, 'gallery');
    }

    public function testThrowsNotFoundIfPageIsNotPublished(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController()(
            $this->createRequest($this->mockPage(isPublic: false)),
            42,
            'gallery',
        );
    }

    public function testThrowsNotFoundIfRootPageIsNotPublished(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController()(
            $this->createRequest($this->mockPage(rootIsPublic: false)),
            42,
            'gallery',
        );
    }

    public function testDownloadsGalleryOfUnpublishedPageInPreviewMode(): void
    {
        $response = $this->createController(previewMode: true)(
            $this->createRequest($this->mockPage(isPublic: false)),
            42,
            'gallery',
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        $this->fs->remove($response->getFile()->getPathname());
    }

    public function testThrowsNotFoundForUnknownModule(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController()(
            $this->createRequest($this->mockPage()),
            99,
            'gallery',
        );
    }

    public function testThrowsNotFoundForUnknownPath(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController()(
            $this->createRequest($this->mockPage()),
            42,
            'unknown',
        );
    }

    public function testThrowsNotFoundIfEventListenerDisablesDownload(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController(downloadEnabled: false)(
            $this->createRequest($this->mockPage()),
            42,
            'gallery',
        );
    }

    private function createController(bool $downloadEnabled = true, bool $previewMode = false): GalleryDownloadController
    {
        $folder = new GalleryFolder(
            slug: 'gallery',
            title: 'Gallery',
            filesystemDirectory: 'gallery',
            trail: ['gallery'],
            metadata: new GalleryMetadata(),
            images: [
                new GalleryImage(
                    uuid: '00000000-0000-0000-0000-000000000001',
                    path: 'files/gallery/image.jpg',
                    filename: 'image.jpg',
                    isCover: false,
                ),
            ],
        );

        $overview = new GalleryOverview(
            root: new GalleryRoot('gallery', 42, '/root/filesystem'),
            folders: [$folder],
            folderIndex: ['gallery' => $folder],
        );

        $folderProvider = $this->createStub(GalleryProviderInterface::class);
        $folderProvider
            ->method('findAllOverviews')
            ->willReturn([$overview])
        ;

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(
                static function (GalleryDownloadActionEvent $event) use ($downloadEnabled): GalleryDownloadActionEvent {
                    if (!$downloadEnabled) {
                        $event->disable();
                    }

                    return $event;
                },
            )
        ;

        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn($previewMode)
        ;

        $imageCollector = new GalleryZipImageCollector();

        return new GalleryDownloadController(
            $folderProvider,
            new GalleryDownloadAvailabilityChecker($imageCollector, $eventDispatcher),
            $imageCollector,
            new GalleryZipCreator($this->projectDir, $this->fs),
            new GalleryDownloadFilenameGenerator(),
            $this->uriSigner,
            $tokenChecker,
        );
    }

    private function createRequest(PageModel $page): Request
    {
        $request = Request::create($this->uriSigner->sign(self::DOWNLOAD_URL));
        $request->attributes->set('pageModel', $page);

        return $request;
    }

    private function mockPage(bool $isPublic = true, bool $rootIsPublic = true): PageModel
    {
        return $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 7,
            'isPublic' => $isPublic,
            'rootIsPublic' => $rootIsPublic,
        ]);
    }
}
