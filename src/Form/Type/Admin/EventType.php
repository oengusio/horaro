<?php

namespace App\Form\Type\Admin;

use App\Horaro\DTO\Admin\UpdateEventDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('website', TextType::class)
            ->add('twitter', TextType::class)
            ->add('twitch', TextType::class)
            ->add('theme', TextType::class)
            ->add('secret', TextType::class)
            ->add('max_schedules', NumberType::class)
            ->add('featured', CheckboxType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateEventDto::class,
        ]);
    }

}
