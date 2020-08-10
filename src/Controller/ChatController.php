<?php


namespace App\Controller;


use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Type\MessageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    /**
     * @Route(path="/", name="chat")
     */
    public function show(Request $request, MessageRepository $repository)
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $objectManager = $this->getDoctrine()->getManager();
            $objectManager->persist($message);
            $objectManager->flush();
            $this->addFlash('success', 'Hallo Welt');
            return $this->redirectToRoute('chat');
        }

        return $this->render('Chat/show.html.twig', [
            'messages' => $repository->findBy([], ['id' => 'DESC'], 10),
            'form' => $form->createView(),
        ]);
    }
}