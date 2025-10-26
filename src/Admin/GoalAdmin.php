<?php

namespace App\Admin;

use App\Entity\Goal;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class GoalAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('description')
            ->add('unit')
            ->add('targetValue')
            ->add('status')
            ->add('week')
            ->add('year')
            ->add('employee')
            ->add('_actions', null, [
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
            ->with('Goal', ['class' => 'col-md-6'])
                ->add('description', TextareaType::class,
                    [
                        'attr' => [
                            'class' => 'ckeditor',
                        ],
                    ])
                ->add('targetValue', NumberType::class, [
                    'scale' => 2,
                ])
                ->add('progress', NumberType::class, [
                    'scale' => 2,
                ])
                ->add('unit', TextType::class)
                ->add('status', ChoiceType::class, [
                    'choices' => [
                        'Active' => 'active',
                        'In Progress' => 'in_progress',
                        'Closed' => 'closed',
                        'Cancelled' => 'cancelled',
                    ],
                ])
                ->add('employee')
            ->end()
            ->with('Period', ['class' => 'col-md-6'])
                ->add('week', IntegerType::class)
                ->add('year', IntegerType::class)
                ->add('createdAt', DateType::class)
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('targetValue')
            ->add('status')
            ->add('week')
            ->add('year')
            ->add('employee')
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('description')
            ->add('targetValue')
            ->add('status')
            ->add('week')
            ->add('year')
            ->add('employee')
        ;
    }

    /** @var Goal object */
    public function preUpdate(object $object): void
    {
        $object->setUpdatedAt(new \DateTimeImmutable());
    }
}
