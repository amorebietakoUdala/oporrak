<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = array_key_exists('data', $options) ? $options['data']['roles'] : null;
        $showDepartment = array_key_exists('data', $options) ? $options['data']['showDepartment'] : null;
        $department = array_key_exists('data', $options) ? $options['data']['department'] : null;
        $locale = $options['locale'];
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => '',
                'query_builder' => function (EntityRepository $ur) use ($department) {
                    $qb = $ur->createQueryBuilder('u');
                    if ($department !== null) {
                        $qb->andWhere('u.department = :department')
                            ->setParameter('department', $department);
                    }
                    $qb->orderBy('u.username', 'ASC');
                    return $qb;
                },
                'label' => 'label.user',
                'multiple' => 'multiple',
            ]);
        if (null !== $roles && $showDepartment && (in_array('ROLE_HHRR', $roles) || in_array('ROLE_ADMIN', $roles))) {
            $builder->add('department', EntityType::class, [
                'class' => Department::class,
                'placeholder' => '',
                'choice_label' => function ($department) use ($locale) {
                    return ($locale === 'es') ? $department->getNameEs() : $department->getNameEu();
                },
                'label' => 'label.department',
                'multiple' => false
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
            'department' => null
        ]);
    }
}
