<?php

namespace App\Form;

use App\Entity\Trick;
use App\Entity\Group;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*$entityManager = $this->getDoctrine()->getManager();
        $entityManager->findAll*/
        $builder
            ->add('description')
            ->add('creationDate', HiddenType::class)
            ->add('modificationDate', HiddenType::class)
            ->add('name')
            /*->add('trickGroup',EntityType::class,[
                'multiple'=> false,
                'expanded'=> false,
                'class'=> Group::class,
                'mapped'=>false
            ])*/
            ->add('trickGroup', EntityType::class, [
                // looks for choices from this entity
                'class' => Group::class,

                // uses the User.username property as the visible option string
                'choice_label' => 'name',

                // used to render a select box, check boxes or radios
                'multiple' => false,
                'expanded' => false,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
