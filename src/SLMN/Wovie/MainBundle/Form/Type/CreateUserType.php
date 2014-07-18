<?php
namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CreateUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array(
                'attr' => array(
                    'invalid_message' => 'username.invalid',
                    'placeholder' => 'username.placeholder',
                )
            ))
            ->add('email', 'email', array(
                'attr' => array(
                    'invalid_message' => 'email.invalid',
                    'placeholder' => 'email.placeholder',
                )
            ))
            ->add('password', 'password', array(
                'attr' => array(
                    'invalid_message' => 'password.invalid',
                    'placeholder' => 'password.placeholder'
                )
            ))
            ->add('createAccount', 'submit', array(
                'attr' => array(
                    'class' => 'expand'
                )
            ))
            ->getForm();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'validation_groups' => function(FormInterface $form) {
                        return array('Default', 'changeProfile');
                    }
            ]);
    }

    public function getName()
    {
        return 'createUser';
    }
}