<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BroadcastType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'text', array(
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('icon', 'text', array(
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('enabled', 'checkbox', array(
                'required' => false
            ))
            ->add('submit', 'submit', array(
                'attr' => array(
                    'class' => 'expand'
                )
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'SLMN\Wovie\MainBundle\Entity\Broadcast',
        ));
    }

    public function getName()
    {
        return 'broadcast';
    }
}
