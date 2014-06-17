<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'email', array(
                'attr' => array(
                    'invalid_message' => 'email.invalid'
                )
            ))
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
                'label' => 'Send message',
                'attr' => array(
                    'class' => 'expand'
                )
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $collectionConstraint = new Collection(array(
            'email' => array(
                new NotBlank(array('message' => 'not_blank')),
                new Email(array('message' => 'contact.email.invalid_message'))
            ),
            'subject' => array(
                new NotBlank(array('message' => 'not_blank')),
                new Length(array(
                    'min' => 3, 'minMessage' => 'contact.subject.min_message',
                    'max' => 100, 'maxMessage' => 'contact.subject.max_message'))
            ),
            'message' => array(
                new NotBlank(array('message' => 'not_blank')),
                new Length(array('min' => 5, 'minMessage' => 'contact.message.min_message'))
            )
        ));

        $resolver->setDefaults(array(
            'constraints' => $collectionConstraint
        ));
    }

    public function getName()
    {
        return 'contact';
    }
}