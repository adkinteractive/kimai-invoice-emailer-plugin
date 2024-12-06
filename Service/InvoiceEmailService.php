<?php

namespace KimaiPlugin\InvoiceEmailerBundle\Service;

use App\Configuration\MailConfiguration;
use App\Configuration\SystemConfiguration;
use App\Entity\Invoice;
use App\Entity\InvoiceMeta;
use App\Event\EmailEvent;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceRepository;
use KimaiPlugin\InvoiceEmailerBundle\EventSubscriber\EmailSentFieldSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvoiceEmailService
{
    public function __construct(
        private SystemConfiguration $systemConfiguration,
        private MailConfiguration $mailConfiguration,
        private ServiceInvoice $serviceInvoice,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private InvoiceRepository $invoiceRepository,
        private EventDispatcherInterface $eventDispatcher,
        private EmailSentFieldSubscriber $emailSentFieldSubscriber,
        private LoggerInterface $logger
    ) {
    }

    private function hasEmailBeenSent(Invoice $invoice): bool
    {
        $meta = $invoice->getMetaField('email_sent_date');
        $this->logger->debug('Checking if email has been sent', [
            'invoice_id' => $invoice->getId(),
            'email_sent_date' => $meta ? $meta->getValue() : null,
        ]);
        return ($meta !== null && $meta->getValue() !== null);
    }

    public function shouldTriggerAutomatically(Invoice $invoice): bool
    {
        $triggerStatus = $this->systemConfiguration->find('invoice.emailer.trigger_status');
        if (empty($triggerStatus)) {
            $this->logger->debug('No trigger status configured, automatic sending disabled');
            return false;
        }

        if ($this->hasEmailBeenSent($invoice)) {
            $this->logger->debug('Email already sent, skipping automatic trigger');
            return false;
        }

        $shouldSend = $invoice->getStatus() === $triggerStatus;

        $this->logger->debug(sprintf(
            'Checking automatic trigger. Status: %s, Trigger: %s, Should Send: %s',
            $invoice->getStatus(),
            $triggerStatus,
            $shouldSend ? 'yes' : 'no'
        ));

        return $shouldSend;
    }

    private function validateCanSend(Invoice $invoice): bool
    {
        if ($invoice->isCanceled()) {
            $this->logger->debug('Cannot send email - invoice is canceled');
            return false;
        }

        return true;
    }

    private function shouldUpdateStatus(Invoice $invoice): bool
    {
        $postEmailStatus = $this->systemConfiguration->find('invoice.emailer.post_email_status');
        $hasEmailBeenSent = $this->hasEmailBeenSent($invoice);
        return !empty($postEmailStatus) && $invoice->getStatus() !== $postEmailStatus && !$hasEmailBeenSent;
    }

    public function shouldSendEmail(Invoice $invoice): bool
    {
        $triggerStatus = $this->systemConfiguration->find('invoice.emailer.trigger_status');
        if (empty($triggerStatus)) {
            $this->logger->debug('[InvoiceEmailService]: No trigger status configured, automatic sending disabled');
            return false;
        }

        $shouldSend = $invoice->getStatus() === $triggerStatus;

        return $shouldSend;
    }

    private function updateEmailSentDate(Invoice $invoice): void
    {
        $meta = $invoice->getMetaField('email_sent_date');
        if (!$meta) {
            $meta = $this->emailSentFieldSubscriber->prepareField(new InvoiceMeta(), $invoice);
        }
        $meta->setValue((new \DateTime())->format('c'));
    }

    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $postEmailStatus = $this->systemConfiguration->find('invoice.emailer.post_email_status');
        $oldStatus = $invoice->getStatus();

        if (!empty($postEmailStatus)) {
            $this->logger->debug(sprintf(
                'Transitioning invoice status from %s to %s',
                $invoice->getStatus(),
                $postEmailStatus
            ));
            
            switch ($postEmailStatus) {
                case Invoice::STATUS_NEW:
                    $invoice->setIsNew();
                    break;
                case Invoice::STATUS_PENDING:
                    $invoice->setIsPending();
                    break;
                case Invoice::STATUS_PAID:
                    $invoice->setIsPaid();
                    break;

                case Invoice::STATUS_CANCELED:
                    $invoice->setIsCanceled();
                    break;

                default:
                    throw new \InvalidArgumentException('Unknown invoice status');
            }
        }
    }

    public function send(Invoice $invoice, bool $isManual = false, ?object $user = null): bool
    {
        $this->logger->debug('Sending invoice email', [
            'invoice_id' => $invoice->getId(),
            'manual' => $isManual,
            'user_id' => $user ? $user->getId() : null,
        ]);

        if (!$this->validateCanSend($invoice)) {
            return false;
        }

        try {
            $customer = $invoice->getCustomer();
            $email = $customer->getEmail();

            if (empty($email)) {
                throw new \Exception('Customer has no email address');
            }

            $invoiceFile = $this->serviceInvoice->getInvoiceFile($invoice);
            if ($invoiceFile === null) {
                throw new \Exception('Invoice file not found');
            }

            $companyName = $this->systemConfiguration->find('theme.branding.company');
            $companyEmail = $this->mailConfiguration->getFromAddress();
            $logoUrl = $this->systemConfiguration->find('theme.branding.logo') ?? null;

            $message = (new TemplatedEmail())
                ->from(new Address($companyEmail, $companyName))
                ->to($email)
                ->subject($this->translator->trans('invoice.emailer.subject', [
                    '{company}' => $companyName,
                    '{number}' => $invoice->getInvoiceNumber(),
                ], 'system-configuration'))
                ->textTemplate('@InvoiceEmailer/send.text.twig')
                ->htmlTemplate('@InvoiceEmailer/send.html.twig')
                ->addPart(new DataPart(
                    new File($invoiceFile->getPathname()),
                    sprintf(
                        '%s - Invoice %s%s',
                        $companyName,
                        $invoice->getInvoiceNumber(),
                        pathinfo($invoiceFile->getPathname(), PATHINFO_EXTENSION) ? '.' . pathinfo($invoiceFile->getPathname(), PATHINFO_EXTENSION) : '.pdf'
                    )
                ))
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'user' => $user,
                    'company' => [
                        'name' => $companyName,
                        'email' => $companyEmail,
                        'logo' => $logoUrl,
                    ],
                ]);

            $this->eventDispatcher->dispatch(new EmailEvent($message));

            if ($this->shouldUpdateStatus($invoice)) {
                $this->updateInvoiceStatus($invoice);
            }

            $this->updateEmailSentDate($invoice);

            $this->invoiceRepository->saveInvoice($invoice);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('[InvoiceEmailService]: Failed to send invoice email: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
            ]);
            return false;
        }
    }
}
