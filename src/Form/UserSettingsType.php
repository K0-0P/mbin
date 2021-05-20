<?php

namespace App\Form;

use App\DTO\UserSettingsDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'notifyOnNewEntry',
                CheckboxType::class
            )
            ->add(
                'notifyOnNewEntryCommentReply',
                CheckboxType::class
            )
            ->add(
                'notifyOnNewPost',
                CheckboxType::class
            )
            ->add(
                'notifyOnNewPostCommentReply',
                CheckboxType::class
            )
            ->add(
                'darkTheme',
                CheckboxType::class
            )
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => UserSettingsDto::class,
            ]
        );
    }
}
