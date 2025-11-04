<?php

namespace App\Form;

use App\Entity\Goal;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $managerTeam = $options['manager_team'];

        $builder
            ->add('description', TextareaType::class,
                [
                    'label' => 'Description',
                    'attr' => [
                        'class' => 'ckeditor',
                    ],
                ])
            ->add('targetValue', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'placeholder' => '2.30',
                ],
            ])
            ->add('progress', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'placeholder' => '1.45',
                ],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unit',
                'choices' => [
                    'Percentage (%)' => '%',
                    'Hours (hrs)' => 'hours',
                    'Items' => 'items',
                    'Tasks' => 'tasks',
                    'Sales (USD)' => 'usd',
                    'Sales (EUR)' => 'eur',
                    'Other (Count)' => 'count',
                ],
                'placeholder' => 'Select a unit',
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'In Progress' => 'in_progress',
                    'Closed' => 'closed',
                    'Cancelled' => 'cancelled',
                ],
            ])
            ->add('employee', EntityType::class, [
                'class' => User::class,
                'placeholder' => 'Select an Employee to assign',
                'choices' => $managerTeam ? $managerTeam->getMembers() : [],
                'choice_label' => function (User $user) {
                    return $user->getFirstname().' '.$user->getLastname();
                },
            ])
            ->add('week', IntegerType::class, [
                'empty_data' => (int) date('W'),
                'attr' => [
                    'placeholder' => 'Leave empty for the current week ('.(int) date('W').')',
                ],
            ])
            ->add('year', IntegerType::class, [
                'empty_data' => (int) date('Y'),
                'attr' => [
                    'placeholder' => 'Leave empty for the current year ('.(int) date('Y').')',
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Goal::class,
        ]);
        $resolver->setRequired('manager_team');
        $resolver->setAllowedTypes('manager_team', Team::class);
    }
}
