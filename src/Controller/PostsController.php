<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class PostsController extends AbstractController
{

    private PostRepository $postRepository;
    private LoggerInterface $logger;


    public function __construct(
        PostRepository  $postRepository,
        LoggerInterface $logger
    )
    {
        $this->postRepository = $postRepository;
        $this->logger = $logger;
    }


    #[Route('/api/pub/posts', name: 'posts', methods: ['GET'])]
    public function index(): Response
    {
        $this->logger->debug('Load posts from database');
        $posts = $this->postRepository->findAll();
        return $this->json($posts);
    }

    #[Route('/api/int/posts', name: 'posts_create', methods: ['PUT'])]
    public function createPost(
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response
    {
        $user = $this->getUser();

        $this->logger->debug("Load post from request");
        $content = $request->getContent();
        $post = $serializer->deserialize($content, Post::class, 'json');

        $post->setAuthor($user->getUserIdentifier());

        $errors = $validator->validate($post);
        if ($errors->count()){
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->logger->debug("Save post into database");
        $em->persist($post);
        $em->flush();

        return $this->json($post);
    }
}
