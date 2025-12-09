<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AMREU\UserBundle\Form\UserType as BaseUserType;
use App\Entity\Department;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class UserType extends BaseUserType
{
    public function __construct($class, $allowedRoles, private int $unionHoursPerMonth)
    {
        parent::__construct($class, $allowedRoles);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('boss', EntityType::class, [
                'class' => User::class,
                'label' => 'user.boss',
                'placeholder' => 'placeholder.choose',
                'disabled' => $options['readonly'],
                'query_builder' => function (UserRepository $userRepo): QueryBuilder {
                    return $userRepo->findAllOrderedByUsernameAndRoleBossQB();
                },
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'user.department',
                'placeholder' => 'placeholder.choose',
                'constraints' => [
                    new NotBlank()
                ],
                'disabled' => $options['readonly'],
                'query_builder' => function (DepartmentRepository $departmentRepo): QueryBuilder {
                    return $departmentRepo->findAllOrderedByNameQB();
                },
            ])
            ->add('yearsWorked', IntegerType::class,[
                'label' => 'user.yearsWorked',
                'constraints' => [
                    new PositiveOrZero()
                ],
                'empty_data' => 0,
                'disabled' => $options['readonly'],
            ])
            ->add('startDate', DateType::class,[
                'label' => 'user.startDate',
                'widget' => 'single_text',
                'html5' => true,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'constraints' => [
                    new NotBlank()
                ],
                'required' => true,
                'disabled' => $options['readonly'],
            ])
            ->add('endDate', DateType::class,[
                'label' => 'user.endDate',
                'widget' => 'single_text',
                'html5' => true,
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'js-datepicker'],
                'required' => false,
                'disabled' => $options['readonly'],
            ])
            ->add('extraDays', IntegerType::class,[
                'label' => 'user.extraDays',
                // 'constraints' => [
                //     new PositiveOrZero()
                // ],
                'empty_data' => '0',
                'disabled' => $options['readonly'],
            ])
            ->add('unionDelegate', CheckboxType::class,[
                'label' => 'user.unionDelegate',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],                
                'disabled' => $options['readonly'],
            ])
            ->add('unionHoursPerMonth', TextType::class,[
                'label' => 'user.unionHoursPerMonth',
                'required' => false,
                'empty_data' => 0,
                'disabled' => $options['readonly'],
                'constraints' => [
                    new PositiveOrZero(),
                ]
            ])
            ->add('worksOnWeekends', CheckboxType::class,[
                'label' => 'user.worksOnWeekends',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],                
                'disabled' => $options['readonly'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_change' => false,
            'readonly' => false,
            'new' => false,
        ]);
    }
}
