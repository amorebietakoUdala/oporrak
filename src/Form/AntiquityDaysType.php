<?php

namespace App\Form;

use App\Entity\AntiquityDays;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AntiquityDaysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $readonly = $options['readonly'];
        $builder
            ->add('id', HiddenType::class)
            ->add('yearsWorking', IntegerType::class, [
                'disabled' => $readonly
            ])
            ->add('vacationDays', IntegerType::class, [
                'disabled' => $readonly
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AntiquityDays::class,
            'readonly' => false,
        ]);
    }
}
