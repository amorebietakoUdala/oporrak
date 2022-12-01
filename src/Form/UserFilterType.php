<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserFilterType extends AbstractType
{

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->user = $tokenStorage->getToken()->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ( null !== $this->user) {
            $roles = array_key_exists('data', $options) ? $this->user->getRoles() : null;
        }
        $showDepartment = array_key_exists('data', $options) ? $options['data']['showDepartment'] : null;
        $department = array_key_exists('data', $options) ? $options['data']['department'] : null;
        $locale = $options['locale'];
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => '',
                'query_builder' => function (UserRepository $ur) use ($department) {
                    return $ur->findUsersByDepartmentOrBossQB($department,$this->user);
                },
                'label' => 'label.user',
                'multiple' => true,
//                'expanded' => false,
            ]);
        if (null !== $roles && $showDepartment && (in_array('ROLE_HHRR', $roles) || in_array('ROLE_ADMIN', $roles))) {
            $builder->add('department', EntityType::class, [
                'class' => Department::class,
                'placeholder' => '',
                'choice_label' => function ($department) use ($locale) {
                    return ($locale === 'es') ? $department->getNameEs() : $department->getNameEu();
                },
                'label' => 'label.department',
                'multiple' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'user' => null,
            'locale' => null,
            'showDepartment' => false,
            'department' => null
        ]);
    }
}
