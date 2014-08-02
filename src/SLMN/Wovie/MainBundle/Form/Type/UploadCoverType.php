<?php

namespace SLMN\Wovie\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UploadCoverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', 'file', array(
                'required' => true
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // TODO: Better validation
        $collectionConstraint = new Collection(array(
            'file' => array(
                new Assert\Image(array(
                    'minWidth' => 100,
                    'minHeight' => 150,
                    'allowLandscape' => false,
                    'allowPortrait' => true,
                    'mimeTypes' => array(
                        'image/jpeg',
                        'image/png'
                    ),
                    'maxSize' => '1M'
                ))
            )
        ));

        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'constraints' => $collectionConstraint
        ));
    }

    public function getName()
    {
        return 'uploadCover';
    }
}