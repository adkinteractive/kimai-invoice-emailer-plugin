<?php

namespace KimaiPlugin\InvoiceEmailerBundle\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Entity\EntityWithMetaFields;
use App\Entity\InvoiceMeta;
use App\Entity\MetaTableTypeInterface;
use App\Event\InvoiceMetaDefinitionEvent;
use App\Event\InvoiceMetaDisplayEvent;
use App\Form\Type\DateTimePickerType;
use App\Form\Type\DateTimeTextType;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use KimaiPlugin\InvoiceEmailerBundle\Form\Type\MetaDateTimePickerType;

class EmailSentFieldSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SystemConfiguration $configuration,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceMetaDefinitionEvent::class => ['loadEmailSentDateMetaField', 200],
            InvoiceMetaDisplayEvent::class => ['addEmailSentField', 75],
        ];
    }

    public function prepareField(MetaTableTypeInterface $definition, EntityWithMetaFields $entity = null): MetaTableTypeInterface
    {
        $timezone = $this->configuration->find('defaults.user.timezone');

        $definition->setName('email_sent_date');
        $definition->setLabel('Email date');
        $definition->setIsVisible(true);
        $definition->setType(MetaDateTimePickerType::class);

        if ($entity !== null) {
            $entity->setMetaField($definition);
        }

        return $definition;
    }

    public function loadEmailSentDateMetaField(InvoiceMetaDefinitionEvent $event): void
    {
        $this->prepareField(new InvoiceMeta(), $event->getEntity());
    }

    public function addEmailSentField(InvoiceMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareField(new InvoiceMeta()));
    }
}
