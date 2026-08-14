<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-folder-gallery-download-extension-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\ContaoFolderGalleryBundle\Tests\DependencyInjection;

use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\Action\DownloadGalleryAction;
use Cgoit\ContaoFolderGalleryDownloadExtensionBundle\DependencyInjection\CgoitFolderGalleryDownloadExtensionExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CgoitFolderGalleryDownloadExtensionExtension::class)]
final class CgoitFolderGalleryDownloadExtensionExtensionTest extends TestCase
{
    private const array SERVICES = [
        DownloadGalleryAction::class,
    ];

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $extension = new CgoitFolderGalleryDownloadExtensionExtension();

        $this->container = new ContainerBuilder();

        $extension->load([], $this->container);
    }

    #[DataProvider('serviceProvider')]
    public function testExtensionLoadsServices(string $serviceId): void
    {
        $this->assertTrue(
            $this->container->hasDefinition($serviceId),
            \sprintf(
                'Service "%s" was not registered by the extension.',
                $serviceId,
            ),
        );

        $definition = $this->container->getDefinition($serviceId);

        $this->assertTrue($definition->isAutowired());
        $this->assertFalse($definition->isAbstract());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function serviceProvider(): iterable
    {
        foreach (self::SERVICES as $serviceId) {
            yield $serviceId => [$serviceId];
        }
    }
}
