<?php

namespace KimaiPlugin\InvoiceEmailerBundle\EventSubscriber;

use App\Entity\Invoice;
use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemConfigurationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigurationEvent::class => ['onSystemConfiguration', 100],
        ];
    }

    public function onSystemConfiguration(SystemConfigurationEvent $event): void
    {
        foreach ($event->getConfigurations() as $configuration) {
            if ($configuration->getSection() !== 'invoice') {
                continue;
            }

            $triggerStatusLabel = $this->translator->trans('invoice.emailer.trigger_status', [], 'system-configuration');
            $configuration->addConfiguration(
                (new Configuration('invoice.emailer.trigger_status'))
                    ->setLabel($triggerStatusLabel)
                    ->setTranslationDomain('messages')
                    ->setRequired(false)
                    ->setType(ChoiceType::class)
                    ->setOptions([
                        'choices' => [
                            'status.' . Invoice::STATUS_NEW => Invoice::STATUS_NEW,
                            'status.' . Invoice::STATUS_PENDING => Invoice::STATUS_PENDING,
                        ],
                    ]),
            );

            $postStatusLabel = $this->translator->trans('invoice.emailer.post_email_status', [], 'system-configuration');
            $configuration->addConfiguration(
                (new Configuration('invoice.emailer.post_email_status'))
                    ->setLabel($postStatusLabel)
                    ->setTranslationDomain('messages')
                    ->setRequired(false)
                    ->setType(ChoiceType::class)
                    ->setOptions([
                        'choices' => [
                            'status.' . Invoice::STATUS_NEW => Invoice::STATUS_NEW,
                            'status.' . Invoice::STATUS_PENDING => Invoice::STATUS_PENDING,
                            'status.' . Invoice::STATUS_PAID => Invoice::STATUS_PAID,
                        ],
                    ]),
            );

            break;
        }
    }
}
