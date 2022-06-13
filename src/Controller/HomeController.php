<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'users' => $userRepository->findAll(),
            'conversations' => $this->getUser()->getConversations(),
        ]);
    }

    #[Route('/conversation/{id}/messages', name: 'app_conversation_messages')]
    public function conversation(Request $request, Conversation $conversation, MessageRepository $messageRepository): Response
    {
        $message = new Message();

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        $message->setConversation($conversation);
        $message->setAuthor($this->getUser());

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->add($message, true);

            return $this->redirectToRoute('app_conversation_messages', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('home/conversation.html.twig', [
            'form' => $form->createView(),
            'messages' => $conversation->getMessages(),
        ]);
    }

    #[Route('/send-message-to/{id}', name: 'app_send_message_to')]
    public function sendMessageTo(Request $request, User $user, ConversationRepository $conversationRepository, MessageRepository $messageRepository): Response
    {
        $conversations = $this->getUser()->getConversations();
        foreach ($conversations as $conversation) {
            if ($conversation->getUsers()->contains($user)) {
                return $this->redirectToRoute('app_conversation_messages', [
                    'id' => $conversation->getId(),
                ]);
            }
        }

        // create conversation
        $conversation = new Conversation();
        $conversation->setStartAt(new \DateTime());
        $conversation->addUser($this->getUser());
        $conversation->addUser($user);

        // create message
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $conversationRepository->add($conversation, true);
            $message->setConversation($conversation);
            $message->setAuthor($this->getUser());

            $messageRepository->add($message, true);

            $conversation->setLastMessage($message);
            $conversationRepository->add($conversation, true);

            return $this->redirectToRoute('app_conversation_messages', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('home/send_message.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
