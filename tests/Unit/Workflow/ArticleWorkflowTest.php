<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractArticleWorkflow;
use Contenir\Mvc\Workflow\Workflow\ArticleWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\NewsController;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\NewsWorkflow;
use Laminas\Router\Http\Literal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractArticleWorkflow::class)]
#[Group('unit')]
final class ArticleWorkflowTest extends TestCase
{
    #[Test]
    public function articlesDefaultToMonthlyChangesAtHalfPriority(): void
    {
        $workflow = new ArticleWorkflow();

        static::assertSame(['monthly', '0.5'], [$workflow->getPageChangeFrequency(), $workflow->getPriority()]);
    }

    #[Test]
    public function routeIsAListingWithAPostChildRoute(): void
    {
        $workflow = new NewsWorkflow();
        $workflow->setResource(ResourceFactory::page(
            id: 17,
            slug: 'news',
        ));

        static::assertSame(
            [
                'type'          => Literal::class,
                'options'       => [
                    'route'    => '/news',
                    'defaults' => [
                        'controller'  => NewsController::class,
                        'action'      => 'index',
                        'resource_id' => ['resource_id' => 17],
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'post' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'       => '[/:slug]',
                            'constraints' => [
                                'slug' => '[a-zA-Z0-9_-]+',
                            ],
                            'defaults'    => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                ],
            ],
            $workflow->getRouteConfig(),
        );
    }

    #[Test]
    public function subclassesOverrideTheSitemapSettings(): void
    {
        $workflow = new NewsWorkflow();

        static::assertSame(['weekly', '1.0'], [$workflow->getPageChangeFrequency(), $workflow->getPriority()]);
    }
}
