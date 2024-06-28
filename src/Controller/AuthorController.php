<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/author')]
class AuthorController extends AbstractController
{
    public function __construct(
        private AuthorRepository $authorRepo ,
     private SerializerInterface $serializer,
     private EntityManagerInterface $em,
     private UrlGeneratorInterface $urlGenerator,
     private ValidatorInterface $validator
     )
    {}

    #[Route('/', name: 'app_author', methods: 'GET')]
    public function index(): JsonResponse
    {
        $authors = $this->authorRepo->findAll();
        $jsonAuthor = $this->serializer->serialize($authors, 'json', ["groups" => "getBooks"]);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_detail_author', methods: 'GET')]
    public function detailAuthor(Author $author): JsonResponse
    {
        if (!$author) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $jsonAuthor = $this->serializer->serialize($author, 'json', ["groups" => "getBooks"]);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_delete_author', methods: 'DELETE')]
    public function removeAuthor(?Author $author): JsonResponse
    {
        if ($author->getBook()) {
            return null ;
        }
        $this->em->remove($author);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/', name: 'app_add_author', methods: 'POST')]
    public function addauthor(Request $request): JsonResponse
    {
        $author = $this->serializer->deserialize($request->getContent(), Author::class, 'json');

               // On vérifie les erreurs
               $errors = $this->validator->validate($author);

               if ($errors->count() > 0) {
                   return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
               }

        $this->em->persist($author);
        $this->em->flush();

        $jsonAuthor = $this->serializer->serialize($author, 'json', ['groups' => 'getBooks']);
        
        $location = $this->urlGenerator->generate('app_detail_author', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name:"updatAuthor", methods:['PUT'])]

    public function updateBook(Request $request, Author $currentAuthor): JsonResponse 
    {
        $updatedBook = $this->serializer->deserialize($request->getContent(), 
        Author::class, 
        'json', 
        [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

        
        $this->em->persist($updatedBook);
        $this->em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
}
