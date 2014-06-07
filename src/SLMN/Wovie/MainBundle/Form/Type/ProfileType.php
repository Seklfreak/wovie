<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('bio', 'textarea', array(
                'required' => false
            ))
            ->add('submit', 'submit', array(
                'attr' => array(
                    'class' => 'expand'
                )
            ));
        // TODO: Validation
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'SLMN\Wovie\MainBundle\Entity\Profile',
        ));
    }

    public function getName()
    {
        return 'profile';
    }
}