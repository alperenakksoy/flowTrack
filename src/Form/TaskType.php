<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $managerTeam = $options['manager_team'];
        $builder
        ->add('title', TextType::class, [
            'label' => 'Title',
            'attr' => [
                'placeholder' => 'Enter task title',
            ],
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'attr' => [
                'rows' => 5,
                'placeholder' => 'Enter task description',
                'class' => 'ckeditor',
            ],
        ])
        ->add('status', ChoiceType::class, [
            'label' => 'Status',
            'choices' => [
                'In progress' => 'In progress',
                'Completed' => 'Completed',
                'Cancelled' => 'Cancelled',
            ],
        ])
        ->add('assignedTo', EntityType::class, [
            'class' => User::class,
            'choices' => $managerTeam ? $managerTeam->getMembers() : [],
            'choice_label' => function (User $user) {
                return $user->getFirstName().' '.$user->getLastName();
            },
        ])
        ->add('dueDate', DateTimeType::class, [
            'widget' => 'single_text',
            'label' => 'Due Date',
        ])
        ->add('priority', ChoiceType::class, [
            'choices' => [
                'Low' => '1',
                'Medium' => '2',
                'High' => '3',
            ],
            'label' => 'Priority',
            'expanded' => false,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);

        $resolver->setRequired('manager_team');
        $resolver->setAllowedTypes('manager_team', Team::class);
    }
}
