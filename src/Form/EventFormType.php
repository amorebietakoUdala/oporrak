<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Status;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\DateAfter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $days = $options['days'];
        $builder
            ->add('id', HiddenType::class)
            ->add('name', ChoiceType::class, [
                'label' => 'event.name',
                'choices' => [
                    'Vacaciones/Oporrak' => 'Vacaciones/Oporrak',
                    'Asuntos particulares/Norbere kontuetarako baimena' => 'Asuntos particulares/Norbere kontuetarako baimena',
                    'Exceso jornada/Gehiegizko lanaldia' => 'Exceso jornada/Gehiegizko lanaldia',
                    'Días antigüedad/Antzinatasun egunak' => 'Días antigüedad/Antzinatasun egunak',
                    'Otros/Besteren bat' => 'Otros/Besteren bat',
                ],
                'empty_data' => 'Vacaciones/Oporrak',
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'event.startDate',
                'constraints' => [
                    new NotBlank(),
                    new DateAfter(['days' => $days])

                ]
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'event.endDate',
                'constraints' => [
                    new NotBlank(),
                    new DateAfter(['days' => $days])
                ]
            ])
            ->add('status', EntityType::class, [
                'attr' => ['class' => 'd-none'],
                'class' => Status::class,
                'label' => 'event.status',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'days' => 5
        ]);
    }
}
