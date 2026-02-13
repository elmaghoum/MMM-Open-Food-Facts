<?php

declare(strict_types=1);

namespace UI\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TwoFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de vérification',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(exactly: 6),
                    new Assert\Regex(pattern: '/^\d{6}$/', message: 'Le code doit contenir 6 chiffres'),
                ],
                'attr' => [
                    'placeholder' => '123456',
                    'class' => 'form-control text-center',
                    'style' => 'font-size: 24px; letter-spacing: 10px;',
                    'maxlength' => 6,
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{6}',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn btn-success w-100',
                ],
            ]);
    }
}