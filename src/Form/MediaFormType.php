<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\MediaType;
use App\Entity\Trick;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*->add('url')*/
            ->add('isMain', CheckboxType::class, [
                'label'    => 'Est ce l\'image principale de la figure?',
                'required' => false,
            ])
            /*->add('mediaType')*/
            ->add('mediaType', EntityType::class, [
                // looks for choices from this entity
                'class' => MediaType::class,

                // uses the group.name property as the visible option string
                'choice_label' => 'type',

                // used to render a select box, check boxes or radios
                'multiple' => false,
                'expanded' => false,])
            ->add('trick',HiddenType::class)
            ->add('url', FileType::class, [
                'label' => 'Image principale (.jpeg, .jpg, .png)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Media details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
