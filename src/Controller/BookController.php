<?php

namespace App\Controller;

use OA\Operation;
use App\Entity\Book;
use OpenApi\Attributes as OA;
use App\Repository\BookRepository;
use App\Service\VersioningService;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


 #[Route('/api/books')]
class BookController extends AbstractController
{
    public function __construct(
        private BookRepository $bookRepo, 
        private SerializerInterface $serializer,
        private EntityManagerInterface $em,
        private AuthorRepository $authorRepo,
        private UrlGeneratorInterface $urlGenerator,
        private ValidatorInterface $validator,
        private TagAwareCacheInterface $cachePool
    )
    { }
    #[Route('/', name: 'app_book', methods: 'GET')]

    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des livres',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Book::class, groups: ['getBooks']))
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'La page que l\'on veut récupérer',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Le nombre d\'éléments que l\'on veut récupérer',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: 'Books',
     description: "Cette méthode permet de récupérer l'ensemble des livres." )]

 /*    public function getAllBooks(Request $request, BookRepository $bookRepo): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBooks-" . $page . "-" . $limit;

        $bookList = $this->cachePool->get($idCache, function (ItemInterface $item) use ($bookRepo, $page, $limit) {
            $item->tag("booksCache");
            return $bookRepo->findAllWithPagination($page, $limit);
            
        });

        //$bookList = $this->bookRepo->findAll();
        //$bookList = $this->bookRepo->findAllWithPagination($page, $limit);

        $jsonBooks = $this->serializer->serialize($bookList, 'json', ["groups" => "getBooks"]);
        return new JsonResponse($jsonBooks, Response::HTTP_OK, [], true);
    } */

    public function getAllBooks(BookRepository $bookRepository, 
    SerializerInterface $serializer, Request $request, 
    VersioningService $versioningService,
    TagAwareCacheInterface $cache): JsonResponse
    {
        $version = $versioningService->getVersion();

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBooks-" . $page . "-" . $limit;
        
        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer, $version) {
            $item->tag("booksCache");
            $bookList = $bookRepository->findAll();
          //  $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getBooks']);
            $context->setVersion($version);
            return $serializer->serialize($bookList, 'json', $context);
        });
      
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
   }

    #[Route('/{id}', name: 'app_detail_book', methods: 'GET')]
    public function detailBook(?Book $book): JsonResponse
    {
        if (!$book) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

      #  $jsonBook = $this->serializer->serialize($book, 'json', ["groups" => "getBooks"]);
      $context = SerializationContext::create()->setGroups(['getBooks']); //avex jmsSerializer
      $jsonBook = $this->serializer->serialize($book, 'json', $context);
        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_delete_book', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un livre')]
    public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
    {
        //use cache
        $cachePool->invalidateTags(["booksCache"]);
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
   /*  public function removeBook(?Book $book): JsonResponse
    {
        if (!$book) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        $this->em->remove($book);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    } */

    #[Route('/', name: 'app_add_book', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]

    public function addBook(Request $request): JsonResponse
    {
        $book = $this->serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $this->validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        // Récupération de l'ensemble des données envoyées sous forme de tableau
         $content = $request->toArray();
        // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
        $idAuthor = $content['idAuthor'] ?? -1;
        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.
        $book->setAuthor($this->authorRepo->find($idAuthor));
        $this->em->persist($book);
        $this->em->flush();

       // $jsonBook = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);

        $context = SerializationContext::create()->setGroups(['getBooks']); //avex jmsSerializer
        $jsonBook = $this->serializer->serialize($book, 'json', $context);

        $location = $this->urlGenerator->generate('app_detail_book', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name:"updateBook", methods:['PUT'])]

    public function updateBook(Request $request, Book $currentBook): JsonResponse 
    {
      /*   $updatedBook = $this->serializer->deserialize($request->getContent(), 
                Book::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
            ); */

            $newBook = $this->serializer->deserialize($request->getContent(), Book::class, 'json');
            $currentBook->setTitle($newBook->getTitle());
            $currentBook->setCoverText($newBook->getCoverText());
     // On vérifie les erreurs
     $errors = $this->validator->validate($currentBook);
     if ($errors->count() > 0) {
         return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
     }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $currentBook->setAuthor($this->authorRepo->find($idAuthor));
        
        $this->em->persist($currentBook);
        $this->em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
}
