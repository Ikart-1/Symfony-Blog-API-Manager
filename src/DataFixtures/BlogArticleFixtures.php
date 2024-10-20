<?php

namespace App\DataFixtures;

use App\Entity\BlogArticle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BlogArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 10; $i++) {
            $article = new BlogArticle();
            $article->setAuthorId($i + 1);
            $article->setTitle('Article de test ' . $i);
            $article->setContent('Contenu de l\'article de test ' . $i);
            $article->setPublicationDate(new \DateTime());
            $article->setCreationDate(new \DateTime());
            $article->setKeywords(['test', 'fixture']);
            $article->setStatus('draft');
            $article->setSlug('article-de-test-' . $i);

            $manager->persist($article);
        }

        $manager->flush();
    }
}