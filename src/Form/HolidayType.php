<?php

namespace App\Form;

use App\Entity\Holiday;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class HolidayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('date', DateType::class, [
                'disabled' => $readonly,
                'widget' => 'single_text',
                'html5' => true,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'holiday.date',
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('descriptionEs', null, [
                'disabled' => $readonly,
                'label' => 'holiday.descriptionEs'
            ])
            ->add('descriptionEu', null, [
                'disabled' => $readonly,
                'label' => 'holiday.descriptionEu'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Holiday::class,
            'readonly' => false,
        ]);
    }
}
