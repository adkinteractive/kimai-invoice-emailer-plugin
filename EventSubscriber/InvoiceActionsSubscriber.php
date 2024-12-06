<?php

namespace KimaiPlugin\InvoiceEmailerBundle\EventSubscriber;

use App\Entity\Invoice;
use App\EventSubscriber\Actions\AbstractActionsSubscriber;
use App\Event\InvoiceCreatedEvent;
use App\Event\InvoiceStatusUpdateEvent;
use App\Event\PageActionsEvent;
use KimaiPlugin\InvoiceEmailerBundle\Service\InvoiceEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class InvoiceActionsSubscriber extends AbstractActionsSubscriber
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $auth,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly InvoiceEmailService $invoiceEmailer,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($auth, $urlGenerator);
    }

    public static function getActionName(): string
    {
        return 'invoice';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();
        $this->logger->debug('InvoiceActionsSubscriber::onActions called', [
            'payload_type' => gettype($payload),
            'has_invoice_key' => is_array($payload) ? array_key_exists('invoice', $payload) : false,
        ]);

        if (!\is_array($payload) || !\array_key_exists('invoice', $payload)) {
            $this->logger->debug('InvoiceActionsSubscriber::onActions returning early - invalid payload');
            return;
        }

        $invoice = $payload['invoice'];
        $this->logger->debug('InvoiceActionsSubscriber::onActions invoice check', [
            'is_invoice' => $invoice instanceof Invoice,
        ]);

        if (!$invoice instanceof Invoice) {
            return;
        }

        if (!$this->auth->isGranted('email_invoice')) {
            return;
        }

        $meta = $invoice->getMetaField('email_sent_date');
        $title = $meta !== null && $meta->getValue() ? 'invoice.emailer_resend' : 'invoice.emailer_send';

        if (!$invoice->isCanceled()) {
            $event->addDivider();
            $event->addAction('email_invoice', [
                'url' => $this->path('invoice_emailer_send', ['id' => $invoice->getId()]),
                'title' => $title,
                'translation_domain' => 'system-configuration',
            ]);
        }
    }
}
