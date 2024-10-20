<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\BlogArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

class BlogArticleControllerTest extends WebTestCase
{
    private $client;
    private $jwtToken;
    private $testUser;
    private $entityManager;
    private $passwordHasher;
    private $tempImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $this->tempImagePath = tempnam(sys_get_temp_dir(), 'test_image_') . '.jpg';
        file_put_contents($this->tempImagePath, 'dummy image content');


        $this->cleanDatabase();

        $this->testUser = $this->createTestUser();
        $this->jwtToken = $this->getJWTToken($this->testUser);
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM blog_article');
        $connection->executeStatement('DELETE FROM user');
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test'.uniqid().'@example.com');

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'test123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getJWTToken(User $user): string
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwtManager->create($user);
    }

    protected function tearDown(): void
    {
        if ($this->tempImagePath && file_exists($this->tempImagePath)) {
            unlink($this->tempImagePath);
        }

        $this->cleanDatabase();
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
        $this->jwtToken = null;
        $this->testUser = null;
        $this->passwordHasher = null;
    }

    public function testGetBlogArticleCollection(): void
    {
        $article = new BlogArticle();
        $article->setTitle('Test Article');
        $article->setContent('Test Content');
        $article->setAuthorId($this->testUser->getId());
        $article->setStatus('draft');
        $article->setSlug('test-article');
        $article->setPublicationDate(new \DateTime());
        $article->setCreationDate(new \DateTime());
        $article->setKeywords([]);

        $this->entityManager->persist($article);
        $this->entityManager->flush();


        $this->client->request('GET', '/api/blog_articles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $content = json_decode($response->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('@context', $content);
        $this->assertArrayHasKey('@id', $content);
        $this->assertArrayHasKey('@type', $content);
        $this->assertArrayHasKey('member', $content);


        $this->assertEquals('Collection', $content['@type']);
        $this->assertNotEmpty($content['member']);


        $firstArticle = $content['member'][0];
        $this->assertEquals('Test Article', $firstArticle['title']);
        $this->assertEquals('Test Content', $firstArticle['content']);
        $this->assertEquals('draft', $firstArticle['status']);
        $this->assertEquals('test-article', $firstArticle['slug']);
    }

    public function testCreateBlogArticle(): array
    {
        $this->client->request(
            'POST',
            '/api/blog_articles',
            [
                'authorId' => $this->testUser->getId(),
                'title' => 'Test Article',
                'content' => 'This is a test article content. The article discusses testing. Article and testing are important topics.', // Modifié pour avoir plus d'occurrences des mots attendus
                'publicationDate' => '2023-05-01T12:00:00+00:00',
                'creationDate' => (new \DateTime())->format(\DateTime::RFC3339),
                'status' => 'draft',
                'slug' => 'test-article'
            ],
            [
                'coverPictureRef' => new UploadedFile(
                    $this->tempImagePath,
                    'test_image.jpg',
                    'image/jpeg',
                    null,
                    true
                )
            ],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
                'CONTENT_TYPE' => 'multipart/form-data',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('keywords', $responseData);
        $this->assertCount(3, $responseData['keywords']);
        $this->assertContains('article', $responseData['keywords']);
        $this->assertContains('testing', $responseData['keywords']);

        return $responseData;
    }
    public function testCreateBlogArticleWithBannedWords(): void
    {
        $this->client->request(
            'POST',
            '/api/blog_articles',
            [
                'authorId' => $this->testUser->getId(),
                'title' => 'Test Article',
                'content' => 'This is an inappropriate and offensive article with vulgar content.',
                'publicationDate' => '2023-05-01T12:00:00+00:00',
                'creationDate' => (new \DateTime())->format(\DateTime::RFC3339),
                'status' => 'draft',
                'slug' => 'test-article-banned'
            ],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
                'CONTENT_TYPE' => 'application/json'
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('banned word', $responseData['violations'][0]['message'] ?? '');
    }

    public function testUpdateBlogArticleKeywords(): void
    {
        $article = $this->testCreateBlogArticle();

        $this->client->request(
            'PATCH',
            '/api/blog_articles/' . $article['id'],
            [
                'content' => 'Software development development development and programming programming. Programming is essential for developers.',
            ],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $updatedArticle = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('keywords', $updatedArticle);
        $this->assertCount(3, $updatedArticle['keywords']);
        $this->assertContains('development', $updatedArticle['keywords']);
        $this->assertContains('programming', $updatedArticle['keywords']);
    }

    public function testGetSingleBlogArticle(): void
    {
        $article = $this->testCreateBlogArticle();

        $this->client->request('GET', '/api/blog_articles/'.$article['id'], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('title', $content);
    }
}