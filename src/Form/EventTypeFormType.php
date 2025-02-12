<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\EventType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EventTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('descriptionEs', null, [
                'disabled' => $readonly,
                'label' => 'eventType.descriptionEs',
            ])
            ->add('descriptionEu', null, [
                'disabled' => $readonly,
                'label' => 'eventType.descriptionEu',
            ])
            ->add('onlyForUnionDelegates', CheckboxType::class, [
                'disabled' => $readonly,
                'label' => 'eventType.onlyForUnionDelegates',
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],                
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventType::class,
            'readonly' => false,
        ]);
    }
}
