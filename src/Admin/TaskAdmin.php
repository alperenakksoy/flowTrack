<?php

namespace App\Admin;

use App\Entity\Task;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @extends AbstractAdmin<Task>
 */
class TaskAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('title', null, ['sortable' => true])
            ->add('description')
            ->add('status')
            ->add('priorityLabel', null, [
                'label' => 'Priority',
                'sortable' => false,
            ])
            ->add('assignedTo', null, [
                'label' => 'Assigned To',
            ])
            ->add('assignedTo.team', null, [
                'label' => 'Team',
            ])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('General', ['class' => 'col-md-6'])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'ckeditor',
                ],
                'empty_data' => '',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Open' => 'open',
                    'In Progress' => 'in_progress',
                    'Closed' => 'closed',
                    'Cancelled' => 'cancelled',
                ],
                'label' => 'Status',
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => '1',
                    'Medium' => '2',
                    'High' => '3',
                ],
                'label' => 'Priority',
            ])
            ->end()
            ->with('Management', ['class' => 'col-md-6'])
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'label' => 'Manager',
                'placeholder' => 'Select a manager',
                'required' => false,
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_MANAGER%')
                        ->orderBy('u.firstName', 'ASC');
                },
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => function ($user) {
                    $team = $user->getTeam() ? ' - '.$user->getTeam()->getTeamName() : '';

                    return $user.$team;
                },
                'label' => 'Assigned To',
            ])
            ->add('dueDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Due Date',
            ])
            ->add('updatedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Updated At',
                'disabled' => true,
            ])
            ->end()
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('title')
            ->add('description')
            ->add('status')
            ->add('priorityLabel')
            ->add('assignedTo')
            ->add('assignedTo.team')
            ->add('createdBy')
        ;
    }

    /* @var Task $object */
    public function preUpdate(object $object): void
    {
        $object->setUpdatedAt(new \DateTimeImmutable());
    }
}
