<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PerformanceReportAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('General Information', ['class' => 'col-md-6'])
                ->add('employee', EntityType::class, [
                    'class' => User::class,
                ])
                ->add('week', IntegerType::class)
                ->add('year', IntegerType::class)
            ->end()

            ->with('Performance', ['class' => 'col-md-6'])
                ->add('score', NumberType::class, [
                    'scale' => 2,
                ])
                ->add('tasksTotal', IntegerType::class)
                ->add('tasksCompleted', IntegerType::class)
                ->add('goalsTotal', IntegerType::class)
                ->add('goalsCompleted', IntegerType::class)
            ->end()

            ->with('Summary & Files', ['class' => 'col-md-12'])
                ->add('summary', TextareaType::class, [
                    'attr' => ['class' => 'ckeditor'],
                ])
                ->add('pdfPath', FileType::class, [
                    'required' => false,
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('employee')
            ->add('week')
            ->add('year')
            ->add('tasksTotal')
            ->add('tasksCompleted')
            ->add('goalsTotal')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('employee')
            ->add('employee.team')
            ->add('week')
            ->add('year')
            ->add('tasksTotal')
            ->add('tasksCompleted')
            ->add('goalsTotal')
            ->add('_actions', null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }


}
