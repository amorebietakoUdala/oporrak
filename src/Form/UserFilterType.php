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
    private User $user;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->user = $tokenStorage->getToken()->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ( null !== $this->user) {
            $roles = array_key_exists('data', $options) ? $this->user->getRoles() : null;
        }
        $showDepartment = array_key_exists('data', $options) ? $options['data']['showDepartment'] : null;
        $department = array_key_exists('data', $options) ? $options['data']['department'] : null;
        $isGrantedHHRR = array_key_exists('data', $options) ? $options['data']['isGrantedHHRR'] : null;
        $isGrantedAdmin = array_key_exists('data', $options) ? $options['data']['isGrantedAdmin'] : null;
        $locale = $options['locale'];
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => '',
                'query_builder' => fn(UserRepository $ur) => $ur->findUsersByDepartmentOrBossQB($department,$this->user),
                'label' => 'label.user',
                'multiple' => true,
//                'expanded' => false,
            ]);
        if (null !== $roles && $showDepartment && ( $isGrantedHHRR || $isGrantedAdmin )) {
            $builder->add('department', EntityType::class, [
                'class' => Department::class,
                'placeholder' => '',
                'choice_label' => fn($department) => ($locale === 'es') ? $department->getNameEs() : $department->getNameEu(),
                'label' => 'label.department',
                'multiple' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'user' => null,
            'locale' => null,
            'showDepartment' => false,
            'department' => null,
            'isGrantedHHRR' => false,
            'isGrantedAdmin' => false,
        ]);
    }
}
