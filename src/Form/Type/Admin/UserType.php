<?php

namespace App\Form\Type\Admin;

use App\Horaro\DTO\Admin\UpdateUserDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class)
            ->add('display_name', TextType::class)
            ->add('gravatar', TextType::class)
            ->add('language', TextType::class)
            ->add('max_events', NumberType::class)
            ->add('role', TextType::class)
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateUserDto::class,
        ]);
    }
}
