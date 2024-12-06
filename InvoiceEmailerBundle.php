<?php

namespace KimaiPlugin\InvoiceEmailerBundle;

use App\Plugin\PluginInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class InvoiceEmailerBundle extends Bundle implements PluginInterface
{
    public function getPermissions(): array
    {
        return [
            'email_invoice' => [
                'role' => 'ROLE_EMAIL_INVOICE',
                'label' => 'Email invoices to customers',
                'permission' => 'email_invoice'
            ],
        ];
    }
}