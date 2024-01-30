<?php

namespace App\Form;

use App\Entity\AntiquityDays;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class AntiquityDaysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('yearsWorked', NumberType::class, [
                'label' => 'antiquityDays.yearsWorked',
                'disabled' => $readonly,
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])
            ->add('vacationDays', NumberType::class, [
                'label' => 'antiquityDays.vacationDays',
                'disabled' => $readonly,
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AntiquityDays::class,
            'readonly' => false,
        ]);
    }
}
