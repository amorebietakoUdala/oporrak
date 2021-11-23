<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\EventType;

class EventTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventType::class,
            'readonly' => false,
        ]);
    }
}
