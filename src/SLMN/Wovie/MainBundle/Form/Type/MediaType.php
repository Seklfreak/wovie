<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('description', 'textarea', array(
                'required' => false
            ))
            ->add('countries', 'text', array(
                'required' => false
            ))
            ->add('writtenBy', 'text', array(
                'required' => false
            ))
            ->add('genres', 'text', array(
                'required' => false
            ))
            ->add('mediaType', 'choice', array(
                'label' => 'Type',
                'choices' => array(
                    '1' => 'Movie',
                    '2' => 'Series'
                ),
                'expanded' => true,
                'multiple' => false
            ))
            ->add('releaseYear', 'integer', array(
                'label' => 'Release of movie or first episode',
                'required' => false,
                'attr' => array(
                    'invalid_message' => 'year.invalid'
                )
            ))
            ->add('finalYear', 'integer', array(
                'label' => 'Release of final episode',
                'required' => false,
                'attr' => array(
                    'invalid_message' => 'year.invalid'
                )
            ))
            ->add('runtime', 'integer', array(
                'label' => 'Runtime (average of episodes)',
                'required' => false,
                'attr' => array(
                    'invalid_message' => 'integer.invalid'
                )
            ))
            ->add('numberOfEpisodes', 'integer', array(
                'label' => 'Number of total episodes',
                'required' => false,
                'attr' => array(
                    'invalid_message' => 'year.invalid'
                )
            ))
            ->add('posterImage', 'url', array(
                'disabled' => true,
                'label' => 'Cover url',
                'required' => false
            ))
            ->add('freebaseId', 'text', array(
                'disabled' => true,
                'required' => false
            ))
            ->add('imdbId', 'text', array(
                'disabled' => true,
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
            'data_class' => 'SLMN\Wovie\MainBundle\Entity\Media',
        ));
    }

    public function getName()
    {
        return 'media';
    }
}