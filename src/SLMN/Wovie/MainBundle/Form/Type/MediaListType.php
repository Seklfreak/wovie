<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MediaListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
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
            'data_class' => 'SLMN\Wovie\MainBundle\Entity\MediaList',
        ));
    }

    public function getName()
    {
        return 'medialist';
    }
}
