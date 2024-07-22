<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthorController extends AbstractController
{
    public function __construct(
        private readonly AuthorRepository $authorRepository,
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $em,
        private readonly BookRepository $bookRepository
    ){}


    #[Route('/api/author', name: 'app_author', methods:['GET'])]
    #[IsGranted("ROLE_ADMIN", message:'Vous n\'avez pas les droits suffisants pour éditer un livre')]
    public function index(): JsonResponse
    {
        $authors = $this->authorRepository->findAll();
        $jsonAuthor = $this->serializer->serialize($authors, 'json');
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/api/author/{id}', name:'detailAuthor', methods:['GET'])]
    #[IsGranted("ROLE_ADMIN", message:'Vous n\'avez pas les droits suffisants pour éditer un livre')]
    public function getDetailAuthor(Author $author)
    {
        $jsonAuthor = $this->serializer->serialize($author, 'json');
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/author', name:'createAuthor', methods:['POST'])]
    #[IsGranted("ROLE_ADMIN", message:'Vous n\'avez pas les droits suffisants pour éditer un livre')]
    public function createAuthor(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $author = $this->serializer->deserialize($request->getContent(), Author::class, 'json');
        $errors = $validator->validate($author);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->em->persist($author);
        $this->em->flush();

        $jsonAuthor = $this->serializer->serialize($author, 'json');

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, [ 'accept' => 'json'], true);
    }
    
    #[Route('/api/author/{id}', name:'updateAuthor', methods:['PUT'])]
    #[IsGranted("ROLE_ADMIN", message:'Vous n\'avez pas les droits suffisants pour éditer un livre')]
    public function updateAuthor(Author $currentAuthor, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $newAuthor = $this->serializer->deserialize($request->getContent(), Author::class, 'json');
        $currentAuthor->setFirstName($newAuthor->getFirstName());
        $currentAuthor->setLastName($newAuthor->getLastName());

        $errors = $validator->validate($currentAuthor);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->em->persist($currentAuthor);
        $this->em->flush();

        // $jsonAuthor = $this->serializer->serialize($currentAuthor, 'json');
        // return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, [ 'accept' => 'json'], true);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

   #[Route('/api/author/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
   #[IsGranted("ROLE_ADMIN", message:'Vous n\'avez pas les droits suffisants pour éditer un livre')]
   public function deleteAuthor(Author $author): JsonResponse 
   {
        if ($this->bookRepository->count(['author' => $author]) > 0) {
            return new JsonResponse([
                'error' => 'Cannot delete author with associated books.'
            ], Response::HTTP_BAD_REQUEST);
        }
        $this->em->remove($author);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
