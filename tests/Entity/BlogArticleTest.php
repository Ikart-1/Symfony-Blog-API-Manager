<?php

namespace App\Tests\Entity;

use App\Entity\BlogArticle;
use PHPUnit\Framework\TestCase;

class BlogArticleTest extends TestCase
{
    private BlogArticle $blogArticle;

    protected function setUp(): void
    {
        $this->blogArticle = new BlogArticle();
    }

    public function testSetAndGetTitle(): void
    {
        $title = 'Test Title';
        $this->blogArticle->setTitle($title);
        $this->assertEquals($title, $this->blogArticle->getTitle());
    }

    public function testSetAndGetContent(): void
    {
        $content = 'Test Content';
        $this->blogArticle->setContent($content);
        $this->assertEquals($content, $this->blogArticle->getContent());
    }

    public function testSetAndGetAuthorId(): void
    {
        $authorId = 1;
        $this->blogArticle->setAuthorId($authorId);
        $this->assertEquals($authorId, $this->blogArticle->getAuthorId());
    }

    public function testSetAndGetCreationDate(): void
    {
        $date = new \DateTime();
        $this->blogArticle->setCreationDate($date);
        $this->assertEquals($date, $this->blogArticle->getCreationDate());
    }

    public function testSetAndGetPublicationDate(): void
    {
        $date = new \DateTime();
        $this->blogArticle->setPublicationDate($date);
        $this->assertEquals($date, $this->blogArticle->getPublicationDate());
    }
}