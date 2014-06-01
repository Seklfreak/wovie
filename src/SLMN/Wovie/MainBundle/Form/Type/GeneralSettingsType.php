<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class GeneralSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('language', 'choice', array(
                'choices' =>
                    array(
                        'en' => 'English',
                        'de' => 'Deutsch'
                    ),
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('submit', 'submit', array(
                'label' => 'Save settings',
                'attr' => array(
                    'class' => 'expand'
                )
            ));
    }

    public function getName()
    {
        return 'generalSettings';
    }
}