<?php

namespace App\Form;

use App\Entity\WorkCalendar;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class WorkCalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('year', NumberType::class, [
                'label' => 'workcalendar.year',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])
            ->add('vacationDays', NumberType::class, [
                'label' => 'workcalendar.vacationDays',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])
            ->add('particularBusinessLeave', NumberType::class, [
                'label' => 'workcalendar.particularBusinessLeave',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])
            ->add('overtimeDays', NumberType::class, [
                'label' => 'workcalendar.overtimeDays',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])
            ->add('workingHours', NumberType::class, [
                'label' => 'workcalendar.workingHours',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                    new NotBlank(),
                ],
                'required' => true,
            ])
            ->add('partitionableDays', NumberType::class, [
                'label' => 'workcalendar.partitionableDays',
                'disabled' => $readonly,
                'constraints' => [
                    new PositiveOrZero(),
                    new NotBlank(),
                ],
                'required' => true,
                'attr' => [
                    'int' => true,
                ]
            ])->add('deadlineNextYear', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'workcalendar.deadlineNextYear',
                'constraints' => [
                    new NotBlank(),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkCalendar::class,
            'readonly' => false,
        ]);
    }
}
