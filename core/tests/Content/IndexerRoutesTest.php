<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Indexer;
use Ava\Content\Item;
use Ava\Testing\TestCase;

final class IndexerRoutesTest extends TestCase
{
    public function testBuildRoutesIncludesUnlistedInExactRoutes(): void
    {
        $indexer = new Indexer($this->app);

        $contentPath = $this->app->configPath('content');
        $item = new Item(
            [
                'title' => 'Unlisted Test',
                'slug' => 'unlisted-test',
                'status' => 'unlisted',
            ],
            '',
            rtrim($contentPath, '/') . '/posts/unlisted-test.md',
            'post'
        );

        $allItems = ['post' => [$item]];
        $contentTypes = [
            'post' => [
                'url' => [
                    'type' => 'pattern',
                    'pattern' => '/posts/{slug}',
                ],
                'templates' => [
                    'single' => 'single.php',
                    'archive' => 'archive.php',
                ],
            ],
        ];

        $method = new \ReflectionMethod($indexer, 'buildRoutes');
        $method->setAccessible(true);

        /** @var array $routes */
        $routes = $method->invoke($indexer, $allItems, $contentTypes, []);

        $this->assertArrayHasKey('exact', $routes);
        $this->assertArrayHasKey('/posts/unlisted-test', $routes['exact']);
    }

    public function testBuildRoutesExcludesDraftFromExactRoutes(): void
    {
        $indexer = new Indexer($this->app);

        $contentPath = $this->app->configPath('content');
        $item = new Item(
            [
                'title' => 'Draft Test',
                'slug' => 'draft-test',
                'status' => 'draft',
            ],
            '',
            rtrim($contentPath, '/') . '/posts/draft-test.md',
            'post'
        );

        $allItems = ['post' => [$item]];
        $contentTypes = [
            'post' => [
                'url' => [
                    'type' => 'pattern',
                    'pattern' => '/posts/{slug}',
                ],
                'templates' => [
                    'single' => 'single.php',
                    'archive' => 'archive.php',
                ],
            ],
        ];

        $method = new \ReflectionMethod($indexer, 'buildRoutes');
        $method->setAccessible(true);

        /** @var array $routes */
        $routes = $method->invoke($indexer, $allItems, $contentTypes, []);

        $this->assertArrayHasKey('exact', $routes);
        $this->assertFalse(isset($routes['exact']['/posts/draft-test']));
    }
}
