<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', 'text', array(
                'attr' => array(
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('message', 'textarea', array(
                'attr' => array(
                    'rows' => 10,
                    'invalid_message' => 'not_blank'
                )
            ))
            ->add('submit', 'submit', array(
                'label' => 'Send feedback',
                'attr' => array(
                    'class' => 'expand'
                )
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $collectionConstraint = new Collection(array(
            'subject' => array(
                new NotBlank(array('message' => 'not_blank')),
                new Length(array(
                    'min' => 3, 'minMessage' => 'feedback.subject.min_message',
                    'max' => 100, 'maxMessage' => 'feedback.subject.max_message'))
            ),
            'message' => array(
                new NotBlank(array('message' => 'not_blank')),
                new Length(array('min' => 5, 'minMessage' => 'feedback.message.min_message'))
            )
        ));

        $resolver->setDefaults(array(
            'constraints' => $collectionConstraint
        ));
    }

    public function getName()
    {
        return 'feedback';
    }
}