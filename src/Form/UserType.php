<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AMREU\UserBundle\Form\UserType as BaseUserType;
use App\Entity\Department;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class UserType extends BaseUserType
{
    public function __construct($class, $allowedRoles)
    {
        parent::__construct($class, $allowedRoles);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('boss', EntityType::class, [
                'class' => User::class,
                'label' => 'user.boss',
                'placeholder' => 'placeholder.choose',
                'disabled' => $options['readonly'],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'user.department',
                'placeholder' => 'placeholder.choose',
                'constraints' => [
                    new NotBlank()
                ],
                'disabled' => $options['readonly'],
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
                'constraints' => [
                    new PositiveOrZero()
                ],
                'empty_data' => '0',
                'disabled' => $options['readonly'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_change' => false,
            'readonly' => false,
            'new' => false,
        ]);
    }
}
