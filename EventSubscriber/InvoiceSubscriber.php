<?php

namespace KimaiPlugin\InvoiceEmailerBundle\EventSubscriber;

use App\Event\InvoiceCreatedEvent;
use App\Event\InvoiceStatusUpdateEvent;
use KimaiPlugin\InvoiceEmailerBundle\Service\InvoiceEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class InvoiceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $auth,
        private readonly InvoiceEmailService $invoiceEmailer,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceCreatedEvent::class => ['onInvoiceCreated', 100],
            InvoiceStatusUpdateEvent::class => ['onInvoiceStatusUpdate', 100],
        ];
    }

    public function onInvoiceCreated(InvoiceCreatedEvent $event): void
    {
        if (!$this->auth->isGranted('email_invoice')) {
            return;
        }

        $invoice = $event->getInvoice();
        $user = null;

        $this->logger->debug('onInvoiceCreated triggered', [
            'invoice_id' => $invoice->getId(),
            'invoice_status' => $invoice->getStatus(),
            'user_id' => $user ? $user->getId() : null,
        ]);

        if ($this->invoiceEmailer->shouldSendEmail($invoice)) {
            $this->logger->debug('Attempting to send email for new invoice', [
                'invoice_id' => $invoice->getId(),
            ]);
            $result = $this->invoiceEmailer->send($invoice, false, $user);
            $this->logger->debug('Email send attempt completed', [
                'invoice_id' => $invoice->getId(),
                'success' => $result,
            ]);
        }
    }

    public function onInvoiceStatusUpdate(InvoiceStatusUpdateEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted('email_invoice')) {
            return;
        }

        $invoice = $event->getInvoice();
        $user = null;

        $this->logger->debug('onInvoiceStatusUpdate triggered', [
            'invoice_id' => $invoice->getId(),
            'invoice_status' => $invoice->getStatus(),
            'user_id' => $user ? $user->getId() : null,
        ]);

        if ($this->invoiceEmailer->shouldSendEmail($invoice)) {
            $this->logger->debug('Attempting to send email for status update', [
                'invoice_id' => $invoice->getId(),
            ]);
            $result = $this->invoiceEmailer->send($invoice, false, $user);
            $this->logger->debug('Email send attempt completed', [
                'invoice_id' => $invoice->getId(),
                'success' => $result,
            ]);
        }
    }
}
