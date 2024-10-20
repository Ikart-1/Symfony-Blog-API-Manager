<?php

namespace App\Controller;

use App\Entity\BlogArticle;
use App\Service\FileUploadService;
use App\Service\WordFrequencyAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[AsController]
class BlogArticleController extends AbstractController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        FileUploadService $fileUploadService,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        WordFrequencyAnalyzer $wordFrequencyAnalyzer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $isPatch = $request->getMethod() === 'PATCH';

            if ($isPatch) {
                $blogArticle = $em->getRepository(BlogArticle::class)->find($request->attributes->get('id'));
                if (!$blogArticle) {
                    return new JsonResponse(['error' => 'Blog article not found'], 404);
                }
            } else {
                $blogArticle = new BlogArticle();
            }

            $logger->debug('Received blog article request', [
                'method' => $request->getMethod(),
                'files' => $request->files->all(),
                'post_data' => $request->request->all()
            ]);
            if ($request->files->has('coverPictureRef')) {
                $coverPicture = $request->files->get('coverPictureRef');
                if ($coverPicture) {
                    $logger->info('Processing cover picture upload', [
                        'original_name' => $coverPicture->getClientOriginalName()
                    ]);

                    try {
                        $fileName = $fileUploadService->upload($coverPicture);
                        $blogArticle->setCoverPictureRef($fileName);
                    } catch (\Exception $e) {
                        $logger->error('Failed to upload cover picture', [
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            }
            if ($authorId = $request->get('authorId')) {
                $blogArticle->setAuthorId((int)$authorId);
            } elseif (!$isPatch) {
                throw new \InvalidArgumentException('authorId is required for new articles');
            }

            if ($title = $request->get('title')) {
                $blogArticle->setTitle($title);
            } elseif (!$isPatch) {
                throw new \InvalidArgumentException('title is required for new articles');
            }

            if ($content = $request->get('content')) {
                $blogArticle->setContent($content);

                $bannedWords = ['the', 'and', 'for', 'that', 'this', 'with', 'from'];

                $keywords = $wordFrequencyAnalyzer->findMostFrequentWords($content, $bannedWords);
                $blogArticle->setKeywords($keywords);
            } elseif (!$isPatch) {
                throw new \InvalidArgumentException('content is required for new articles');
            }

            if ($publicationDate = $request->get('publicationDate')) {
                $blogArticle->setPublicationDate(new \DateTime($publicationDate));
            } elseif (!$isPatch) {
                $blogArticle->setPublicationDate(new \DateTime());
            }

            if (!$isPatch) {
                $blogArticle->setCreationDate(new \DateTime());
            }

            if ($keywords = $request->get('keywords')) {
                $keywordsArray = json_decode($keywords, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $blogArticle->setKeywords($keywordsArray);
                } else {
                    throw new \InvalidArgumentException('Invalid JSON format for keywords');
                }
            }

            if ($status = $request->get('status')) {
                $blogArticle->setStatus($status);
            } elseif (!$isPatch) {
                $blogArticle->setStatus('draft');
            }

            if ($slug = $request->get('slug')) {
                $blogArticle->setSlug($slug);
            } elseif (!$isPatch) {
                throw new \InvalidArgumentException('slug is required for new articles');
            }

            $violations = $validator->validate($blogArticle);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = [
                        'property' => $violation->getPropertyPath(),
                        'message' => $violation->getMessage(),
                    ];
                }
                return new JsonResponse(['violations' => $errors], 400);
            }
            try {
                if (!$isPatch) {
                    $em->persist($blogArticle);
                }
                $em->flush();
                $logger->info('Successfully saved blog article', ['id' => $blogArticle->getId()]);
            } catch (\Exception $e) {
                $logger->error('Failed to save blog article', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            $jsonContent = $serializer->serialize($blogArticle, 'json', ['groups' => 'blog_article:read']);
            return new JsonResponse($jsonContent, $isPatch ? 200 : 201, [], true);

        } catch (\Exception $e) {
            $logger->error('Error processing blog article request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Error processing request',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}