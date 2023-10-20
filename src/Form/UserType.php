<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => "Nom d'utilisateur"])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Tapez le mot de passe à nouveau'],    
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse email'])
            ->add('roles', ChoiceType::class, [
                'choices' => User::ROLES_LIST,
                'multiple' => true,
                'expanded' => true,
                // 'multiple' => false,
                // 'expanded' => false,
                // 'mapped' => false,
            ])
            // ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
        ;
    }

    public function onPreSubmit(FormEvent $event): void
    {        
        $roles[] = $event->getData()['roles'];
        $data = $event->getData();
        $data['roles'] = $roles;

        $user = $event->getForm()->getData();
        $user->setRoles($roles);

        $event->setData($data);
// dd($event->getData());
        // $data = $event->getData();
        // $rolesString = $data['roles'];
        // $rolesArray = [$rolesString];
        // $data['roles'] = $rolesArray;
        // $event->setData($data);
    }

}
