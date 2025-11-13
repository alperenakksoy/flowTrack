<?php

namespace App\Admin;

use App\Entity\Team;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @extends AbstractAdmin<Team>
 */
class TeamAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('team_name', TextType::class, [
                'label' => 'Team Name',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('manager', EntityType::class, [
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
            ->add('members', EntityType::class, [
                'class' => User::class,
                'label' => 'Members',
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
            ])
            ->add('createdAt')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('team_name')
            ->add('manager')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('team_name')
            ->add('manager')
            ->add('members')
            ->add('_actions', null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('team_name')
            ->add('manager')
        ;
    }
}
