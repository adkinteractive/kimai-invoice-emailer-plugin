<?php

namespace KimaiPlugin\InvoiceEmailerBundle\Controller;

use App\Configuration\LocaleService;
use App\Configuration\MailConfiguration;
use App\Configuration\SystemConfiguration;
use App\Entity\Invoice;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceRepository;
use App\Utils\FileHelper;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\InvoiceEmailerBundle\EventSubscriber\EmailSentFieldSubscriber;
use KimaiPlugin\InvoiceEmailerBundle\Service\InvoiceEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/invoice')]
#[IsGranted('email_invoice')]
final class InvoiceEmailerController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private MailerInterface $mailer,
        private ServiceInvoice $serviceInvoice,
        private InvoiceRepository $invoiceRepository,
        private FileHelper $fileHelper,
        private SystemConfiguration $configuration,
        private MailConfiguration $mailConfiguration,
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private EmailSentFieldSubscriber $emailSentFieldSubscriber,
        private LocaleService $localeService,
        private InvoiceEmailService $invoiceEmailService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/emailer/send/{id}', name: 'invoice_emailer_send', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('access', subject.getCustomer())"), 'invoice')]
    public function emailInvoice(Invoice $invoice): RedirectResponse
    {
        try {
            $customer = $invoice->getCustomer();
            $email = $customer->getEmail();
            $user = $this->getUser();

            if (empty($email)) {
                throw new \Exception('Customer has no email address');
            }

            $invoiceFile = $this->serviceInvoice->getInvoiceFile($invoice);
            if ($invoiceFile === null) {
                throw new \Exception('Invoice file not found');
            }

            $this->logger->debug('Manual invoice email requested', [
                'invoice_id' => $invoice->getId(),
                'user_id' => $user ? $user->getId() : null,
            ]);

            $success = $this->invoiceEmailService->send($invoice, true, $user);

            if ($success) {
                $this->addFlash('success', $this->translator->trans('invoice.emailer_sent', [], 'system-configuration'));
            } else {
                $this->addFlash('error', $this->translator->trans('invoice.emailer_error', [], 'system-configuration'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_invoice_list');
    }
}
