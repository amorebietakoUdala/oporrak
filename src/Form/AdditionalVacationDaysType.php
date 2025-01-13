<?php

namespace App\Form;

use App\Entity\AdditionalVacationDays;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class AdditionalVacationDaysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('yearsWorked', NumberType::class, [
                'label' => 'additionalVacationDays.yearsWorked',
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
                'label' => 'additionalVacationDays.vacationDays',
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
            'data_class' => AdditionalVacationDays::class,
            'readonly' => false,
        ]);
    }
}
