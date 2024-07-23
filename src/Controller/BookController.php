<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookController extends AbstractController
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly AuthorRepository $authorRepository,
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $em,
    )
    {}

    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $bookList = $this->bookRepository->findAll();

        // $page = $request->get('page', 1);
        // $limit = $request->get('limit', 3);
        // $bookList = $this->bookRepository->findAllWithPagination($page, $limit);

        $jsonBookList = $this->serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    public function getDetailBook(Book $book): JsonResponse {

        $jsonBook = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBook, Response::HTTP_OK, ['accept' => 'json'], true);
   }

   #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
   public function deleteBook(Book $book): JsonResponse 
   {
       $this->em->remove($book);
       $this->em->flush();

       return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }

//    #[Route('/api/books', name:"createBook", methods: ['POST'])]
//    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a book')]
//    public function createBook(Request $request, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
//    {
//        $book = $this->serializer->deserialize($request->getContent(), Book::class, 'json');
       
//         // On vérifie les erreurs
//         $errors = $validator->validate($book);

//         if ($errors->count() > 0) {
//             return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
//         }
       
//         $content = $request->toArray();
//         $idAuthor = $content['idAuthor'] ?? -1;
//         $book->setAuthor($this->authorRepository->find($idAuthor));

//         // Handle image upload
//         if ($request->files->has('image')) {
//             $imageFile = $request->files->get('image');
//             $imageFilename = uniqid() . '.' . $imageFile->guessExtension();

//             try {
//                 $imageFile->move(
//                     $this->getParameter('kernel.project_dir') . '/public/uploads/images',
//                     $imageFilename
//                 );
//                 $book->setImage($imageFilename);
//             } catch (FileException $e) {
//                 return new JsonResponse(['error' => 'Failed to upload image'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
//             }
//         }

//        $this->em->persist($book);
//        $this->em->flush();

//        $jsonBook = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);
       
//        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

//        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
//    }

    // 
    #[Route('/api/books', name: "createBook", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a book')]
    public function createBook(Request $request, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
    {
        $book = new Book();
        
        $book->setTitle($request->get('title'));
        $book->setCoverText($request->get('coverText'));

        // Validate the author
        // $idAuthor = $request->get('idAuthor') ?? -1;
        // $author = $this->authorRepository->find($idAuthor);
        // if (!$author) {
        //     return new JsonResponse(['error' => 'Author not found'], Response::HTTP_BAD_REQUEST);
        //     // $author === null;
        // }
        // $book->setAuthor($author);
        
        $author = $request->get('idAuthor');
        $idAuthor = $author ?? -1;
        $book->setAuthor($this->authorRepository->find($idAuthor));

        // Handle image upload
        if ($request->files->has('image')) {
            $imageFile = $request->files->get('image');
            $imageFilename = uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                    $imageFilename
                );
                $book->setImage($imageFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Failed to upload image'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Validate the book entity
        $errors = $validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->em->persist($book);
        $this->em->flush();

        $jsonBook = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);
        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

   #[Route('/api/books/{id}', name:"updateBook", methods:['PUT'])]
   #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer un livre')]
   public function updateBook(Request $request, Book $currentBook, ValidatorInterface $validator)
   {
        $newBook = $this->serializer->deserialize($request->getContent(), Book::class, 'json');
        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());

        // On vérifie les erreurs
        $errors = $validator->validate($currentBook);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $currentBook->setAuthor($this->authorRepository->find($idAuthor));

        $this->em->persist($currentBook);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

