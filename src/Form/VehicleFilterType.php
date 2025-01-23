<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => 'Rechercher',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Marque ou modèle...',
                    'class' => 'form-control'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de véhicule',
                'required' => false,
                'choices' => Vehicle::getTypes(),
                'placeholder' => 'Tous les types',
                'attr' => ['class' => 'form-select']
            ])
            ->add('maxPrice', NumberType::class, [
                'label' => 'Prix maximum par jour',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Prix max...',
                    'class' => 'form-control'
                ]
            ])
            ->add('available', ChoiceType::class, [
                'label' => 'Disponibilité',
                'required' => false,
                'choices' => [
                    'Tous' => null,
                    'Disponible' => true,
                    'Non disponible' => false
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
} 