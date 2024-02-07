<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProgrammerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', 'text', [
                // nickname only writeable when we're adding a new programmer
                // disabled nickname text box when editing
                'disabled' => $options['is_edit']
            ])
            ->add('avatarNumber', 'choice', [
                'choices' => [
                    // the key is the value that will be set
                    // the value/label isn't shown in an API, and could
                    // be set to anything
                    1 => 'Girl (green)',
                    2 => 'Boy',
                    3 => 'Cat',
                    4 => 'Boy with Hat',
                    5 => 'Happy Robot',
                    6 => 'Girl (purple)',
                ]
            ])
            ->add('tagLine', 'textarea')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Programmer',
            'is_edit' => false,
            'csrf_protection' => false,
            // turn off csrf_protection
        ));
    }

    public function getName()
    {
        return 'programmer';
    }
}