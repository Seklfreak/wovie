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
                'empty_value' => false,
                'required' => false,
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('publicProfile', 'checkbox', array(
                'label' => 'Enable public profile page?',
                'required' => false,
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // TODO: Better validation
        $collectionConstraint = new Collection(array(
            'language' => array(
                new NotBlank(array('message' => 'not_blank'))
            ),
            'publicProfile' => array(
            )
        ));

        $resolver->setDefaults(array(
            'constraints' => $collectionConstraint
        ));
    }

    public function getName()
    {
        return 'generalSettings';
    }
}