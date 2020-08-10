<?php


namespace App\Type;


use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user');
        $builder->add('body');

        // recover the username from the session if available
        $builder->get('user')->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            if ($event->getData() !== null) {
                return;
            }

            $sessionUsername = $this->requestStack->getCurrentRequest()->getSession()->get('username');
            if ($sessionUsername === null) {
                return;
            }

            $event->setData($sessionUsername);
        });

        // store the given username in the session for future forms
        $builder->get('user')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->requestStack->getCurrentRequest()->getSession()->set('username', $event->getData());
        });
    }
}