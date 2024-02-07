<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UpdateProgrammerType extends ProgrammerType
{
    // The whole purpose of this class is to act just like ProgrammerType , 
    // but set is_edit to true instead of us passing that in the controller.
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(['is_edit' => true]);
    }

    public function getName()
    {
        return 'programmer_edit';
    }
}