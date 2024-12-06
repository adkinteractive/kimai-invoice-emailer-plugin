<?php

namespace KimaiPlugin\InvoiceEmailerBundle\Form\Type;

use App\Form\Type\DateTimePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MetaDateTimePickerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (empty($value)) {
                    return null;
                }
                return new \DateTime($value);
            },
            function ($value) {
                if ($value instanceof \DateTime) {
                    return $value->format('c');
                }
                return null;
            }
        ));
    }

    public function getParent(): string
    {
        return DateTimePickerType::class;
    }
}