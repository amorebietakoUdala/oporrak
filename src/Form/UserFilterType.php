<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = array_key_exists('data', $options) ? $options['data']['roles'] : null;
        $showDepartment = array_key_exists('data', $options) ? $options['data']['showDepartment'] : null;;
        $locale = $options['locale'];
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => '',
                'label' => 'label.user'
            ]);
        // ->add('roles')
        // ->add('password')
        // ->add('firstName')
        // ->add('email')
        // ->add('activated')
        // ->add('lastLogin')
        // ->add('boss')
        if (null !== $roles && $showDepartment && (in_array('ROLE_HHRR', $roles) || in_array('ROLE_ADMIN', $roles))) {
            $builder->add('department', EntityType::class, [
                'class' => Department::class,
                'choice_label' => function ($department) use ($locale) {
                    return ($locale === 'es') ? $department->getNameEs() : $department->getNameEu();
                },
                'placeholder' => '',
                'label' => 'label.department'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'roles' => null,
            'locale' => null,
            'showDepartment' => false,
        ]);
    }
}
