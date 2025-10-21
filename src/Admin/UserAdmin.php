<?php

namespace App\Admin;

use App\Entity\Team;
use App\Entity\User;
use App\Security\UserRole;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class UserAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('email', EmailType::class)
            ->add('roles', ChoiceType::class, [
                'choices' => UserRole::getChoices(),
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('plainPassword', PasswordType::class, ['required' => false])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'label' => 'Team',
                'placeholder' => 'Select team',
                'required' => false,
            ])
            ->add('updatedAt')
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('firstName')
            ->add('lastName')
            ->add('email')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('roles', null, [
                'label' => 'Roles',
            ])
            ->add('team', null, [
                'label' => 'Team',
                'associated_property' => 'team_name',
            ])
            ->add('createdAt', null, [
                'format' => 'Y-m-d H:i:s',
            ])
            ->add('updatedAt', null, [
                'label' => 'Last Updated',
                'format' => 'Y-m-d H:i:s',
            ])->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    private function updatePassword(User $user): void
    {
        $user->setPassword(password_hash($user->getPlainPassword(), PASSWORD_DEFAULT));
    }

    /** @param User $object */
    public function preUpdate(object $object): void
    {
        if (!is_null($object->getPlainPassword())) {
            $this->updatePassword($object);
        }
        /* @var User $object */
        $object->setUpdatedAt(new \DateTimeImmutable());
    }

    protected function prePersist(object $object): void
    {
        $this->updatePassword($object);
    }
}
