<?php

namespace App\Form\Type\Admin;

use App\Horaro\DTO\Admin\UpdateScheduleDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('timezone', TimezoneType::class)
            ->add('start_date', TextType::class)
            ->add('start_time', TextType::class)
            ->add('website', UrlType::class)
            ->add('twitter', TextType::class)
            ->add('twitch', TextType::class)
            ->add('theme', TextType::class)
            ->add('secret', TextType::class)
            ->add('secret', TextType::class)
            ->add('max_items', NumberType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateScheduleDto::class,
        ]);
    }

}
