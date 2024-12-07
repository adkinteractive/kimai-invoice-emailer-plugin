<?php

namespace KimaiPlugin\InvoiceEmailerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class InvoiceEmailerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('kimai', [
            'permissions' => [
                'roles' => [
                    'ROLE_SUPER_ADMIN' => [
                        'email_invoice',
                    ],
                    'ROLE_ADMIN' => [
                        'email_invoice',
                    ],
                    'ROLE_EMAIL_INVOICE' => [
                        'email_invoice',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('security', [
            'role_hierarchy' => [
                'ROLE_SUPER_ADMIN' => ['ROLE_EMAIL_INVOICE']
            ]
        ]);
    }
}