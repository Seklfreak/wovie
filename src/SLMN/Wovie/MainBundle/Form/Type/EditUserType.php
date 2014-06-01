<?php
namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EditUserType extends AbstractType
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
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'password.repeat',
                'options' => array(
                    'attr' => array(
                        'invalid_message' => 'password.invalid'
                    )
                ),
                'required' => false,
                'first_options'  => array('label' => 'password.new.input.first'),
                'second_options' => array('label' => 'password.new.input.second'),
            ))
            ->add('roles', 'entity', array(
                'class' => 'SeklMainUserBundle:Role',
                'property'     => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false
            ))
            ->add('Submit', 'submit', array(
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
                        if ($form->get('password')->getData() == '') {
                            return 'changeProfile';
                        }

                        return 'Default';
                    }
            ]);
    }

    public function getName()
    {
        return 'editUser';
    }
}