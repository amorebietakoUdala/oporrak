<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Status;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\DateAfter;
use App\Validator\Prueba;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Positive;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $days = $options['days'];
        $locale = $options['locale'];
        $hhrr = $options['hhrr'] ?? false;
        $edit = $options['edit'] ?? false;
        $builder
            ->add('id', HiddenType::class)
            ->add('type', EntityType::class, [
                'class' => EventType::class,
                'label' => 'event.type',
                'choice_label' => function ($type) use ($locale) {
                    if ('es' === $locale) {
                        return $type->getDescriptionEs();
                    } else {
                        return $type->getDescriptionEu();
                    }
                },
            ]);
        if (!$edit && !$hhrr) {
            $constraints = [
                new NotBlank(),
                new DateAfter(['days' => $days])
            ];
        } else {
            $constraints = [
                new NotBlank(),
            ];
        }
        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'event.startDate',
                'constraints' => $constraints,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'event.endDate',
                'constraints' => $constraints
                ]);
        $builder
            ->add('halfday', CheckboxType::class, [
                'label' => 'event.halfday',
                'attr' => ['class' => 'js-halfDay'],
                'required' => false,
            ])
            ->add('hours', NumberType::class, [
                'label' => 'event.hours',
                'required' => false,
                'constraints' => [
                    new Positive(),
                ]
            ])
            ->add('usePreviousYearDays', CheckboxType::class, [
                'label' => 'event.usePreviousYearDays',
                'required' => false,
            ]);
            if ($hhrr) {
                $builder
                    ->add('user', EntityType::class, [
                        'class' => User::class,
                        'label' => 'event.user',
                        'query_builder' => fn(UserRepository $er): QueryBuilder => $er->findByActivedQB(true),                        
                    ])
                    ->add('status', EntityType::class, [
                        'class' => Status::class,
                        'label' => 'event.status',
                        'choice_label' => fn($status) => $status->getDescription($locale),
                    ]);
            } else {
                $builder->add('status', EntityType::class, [
                    'attr' => ['class' => 'd-none'],
                    'class' => Status::class,
                    'label' => false,
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'days' => 5,
            'locale' => 'eu',
            'hhrr' => false,
            'edit' => false,
        ]);
    }
}
