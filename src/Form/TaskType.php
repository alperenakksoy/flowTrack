<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Team;
use App\Entity\User;


use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $managerTeam = $options['manager_team'];
        $builder
        ->add('title')
        ->add('description')
        ->add('status')
        ->add('assignedTo', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'firstname',
            'query_builder' => function (UserRepository $er) use ($managerTeam) {
                return $er->createQueryBuilder('u')
                ->where('u.team = :team')
                ->setParameter('team', $managerTeam)
                ->orderBy('u.firstName', 'ASC');
            },
        ])
        ->add('dueDate')
        ->add('priority', ChoiceType::class, [
            'choices' => [
                'Low' => '1',
                'Medium' => '2',
                'High' => '3',
            ],
            'label' => 'Priority',
        ])
        ->add('completedAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'manager_team' => null,
        ]);
        $resolver->setRequired('manager_team');
        $resolver->setAllowedTypes('manager_team', Team::class);
    }
}
