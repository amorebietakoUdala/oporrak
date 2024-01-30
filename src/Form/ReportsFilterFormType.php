<?php

namespace App\Form;

use App\DTO\ReportsFilterFormDTO;
use App\Entity\Department;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportsFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locale = $options['locale'];

        $builder
        ->add('startDate', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'yyyy-MM-dd',
            'attr' => ['class' => 'js-datepicker'],
            'label' => 'event.startDate',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('endDate', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'yyyy-MM-dd',
            'attr' => ['class' => 'js-datepicker'],
            'label' => 'event.endDate',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'username',
            'placeholder' => '',
            'query_builder' => function (EntityRepository $ur) {
                $qb = $ur->createQueryBuilder('u');
                $qb->orderBy('u.username', 'ASC');
                return $qb;
            },
            'label' => 'label.user',
        ])
        ->add('department', EntityType::class, [
            'class' => Department::class,
            'placeholder' => '',
            'choice_label' => fn($department) => ($locale === 'es') ? $department->getNameEs() : $department->getNameEu(),
            'label' => 'label.department',
            'multiple' => false
        ])
    ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'locale' => 'eu',
            'class' => ReportsFilterFormDTO::class,
        ]);
    }
}
