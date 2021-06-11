<?php

namespace App\Form;

use App\Entity\Department;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('nameEs', null, [
                'label' => 'label.departmentNameEs',
                'disabled' => $readonly
            ])
            ->add('nameEu', null, [
                'label' => 'label.departmentNameEu',
                'disabled' => $readonly
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Department::class,
            'readonly' => false,
        ]);
    }
}
