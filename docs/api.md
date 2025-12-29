# API

Ava doesn't force a specific API on you. Instead, it gives you the tools to build exactly the API you need.

## Building a JSON API

Since Ava is just PHP, you can easily create endpoints that return JSON. This is great if you want to use Ava as a headless CMS for a mobile app or a JavaScript frontend.

### Example: A Simple Read-Only API

You can create a plugin to expose your content as JSON.

```php
// plugins/json-api/plugin.php

return [
    'name' => 'JSON API',
    'boot' => function($app) {
        $router = $app->router();
        
        // Endpoint: /api/posts
        $router->addRoute('/api/posts', function() {
            $repo = \Ava\Application::getInstance()->repository();
            $posts = $repo->published('post');
            
            // Return JSON response
            return \Ava\Http\Response::json([
                'data' => array_map(fn($p) => [
                    'title' => $p->title(),
                    'slug' => $p->slug(),
                ], $posts)
            ]);
        });
    }
];
```

Now, visiting `/api/posts` will give you a clean JSON list of your blog posts.
    "success": false,
    "error": "Error message"
}
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/posts` | List all published posts |
| GET | `/api/posts/{slug}` | Get single post by slug |
| GET | `/api/pages` | List all published pages |
| GET | `/api/pages/{slug}` | Get single page by slug |

## Building Custom Endpoints

### Basic Route

```php
$router->addRoute('/api/custom', function($request, $params) {
    $response = \Ava\Http\Response::json([
        'message' => 'Hello from API!'
    ]);
    
    return new \Ava\Routing\RouteMatch(
        type: 'api',
        template: '__raw__',
        params: ['response' => $response]
    );
});
```

### Route with Parameters

```php
$router->addRoute('/api/content/{type}/{slug}', function($request, $params) {
    $type = $params['type'];
    $slug = $params['slug'];
    
    $repo = \Ava\Application::getInstance()->repository();
    $item = $repo->get($type, $slug);
    
    // Return JSON...
});
```

### Query Parameters

```php
$router->addRoute('/api/search', function($request, $params) {
    $query = $request->query('q', '');
    $limit = (int) $request->query('limit', 10);
    
    // Perform search...
});
```

### Prefix Routes

Handle all routes under a path:

```php
$router->addPrefixRoute('/api/v2/', function($request, $params) {
    $path = $request->path();
    // Route based on path...
});
```

## Authentication

### API Key Authentication

```php
function authenticateApiRequest($request): bool {
    $apiKey = $request->header('X-API-Key') 
           ?? $request->query('api_key');
    
    $validKeys = \Ava\Application::getInstance()
        ->config('api.keys', []);
    
    return in_array($apiKey, $validKeys, true);
}

// In your route:
$router->addRoute('/api/private', function($request) {
    if (!authenticateApiRequest($request)) {
        return new \Ava\Routing\RouteMatch(
            type: 'api',
            template: '__raw__',
            params: ['response' => \Ava\Http\Response::json(
                ['error' => 'Unauthorized'],
                401
            )]
        );
    }
    
    // Handle authenticated request...
});
```

### Config for API Keys

```php
// app/config/ava.php
return [
    // ...
    'api' => [
        'keys' => [
            'your-secret-api-key-here',
        ],
    ],
];
```

## Pagination

```php
$router->addRoute('/api/posts', function($request) {
    $page = max(1, (int) $request->query('page', 1));
    $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
    
    $repo = \Ava\Application::getInstance()->repository();
    $allPosts = $repo->published('post');
    
    // Sort by date
    usort($allPosts, fn($a, $b) => 
        ($b->date()?->getTimestamp() ?? 0) - ($a->date()?->getTimestamp() ?? 0)
    );
    
    $total = count($allPosts);
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    $posts = array_slice($allPosts, $offset, $perPage);
    
    return jsonResponse([
        'data' => array_map(fn($p) => [
            'title' => $p->title(),
            'slug' => $p->slug(),
        ], $posts),
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ],
    ]);
});
```

## Taxonomy Endpoints

```php
// List all categories
$router->addRoute('/api/categories', function($request) {
    $repo = \Ava\Application::getInstance()->repository();
    $terms = $repo->terms('category');
    
    return jsonResponse(array_map(fn($term) => [
        'name' => $term,
        'slug' => \Ava\Support\Str::slug($term),
        'url' => '/category/' . \Ava\Support\Str::slug($term),
    ], $terms));
});

// Posts by category
$router->addRoute('/api/categories/{slug}/posts', function($request, $params) {
    $repo = \Ava\Application::getInstance()->repository();
    $posts = $repo->query('post')
        ->published()
        ->whereTerm('category', $params['slug'])
        ->get();
    
    return jsonResponse(array_map(fn($p) => [
        'title' => $p->title(),
        'slug' => $p->slug(),
    ], $posts));
});
```

## Search Endpoint

```php
$router->addRoute('/api/search', function($request) {
    $query = trim($request->query('q', ''));
    
    if (strlen($query) < 2) {
        return jsonResponse([
            'results' => [],
            'message' => 'Query too short',
        ]);
    }
    
    $repo = \Ava\Application::getInstance()->repository();
    $results = [];
    
    foreach (['page', 'post'] as $type) {
        foreach ($repo->published($type) as $item) {
            $inTitle = stripos($item->title(), $query) !== false;
            $inExcerpt = stripos($item->excerpt() ?? '', $query) !== false;
            $inContent = stripos($item->rawContent(), $query) !== false;
            
            if ($inTitle || $inExcerpt || $inContent) {
                $results[] = [
                    'type' => $type,
                    'title' => $item->title(),
                    'slug' => $item->slug(),
                    'excerpt' => $item->excerpt(),
                    'relevance' => $inTitle ? 3 : ($inExcerpt ? 2 : 1),
                ];
            }
        }
    }
    
    // Sort by relevance
    usort($results, fn($a, $b) => $b['relevance'] - $a['relevance']);
    
    return jsonResponse([
        'query' => $query,
        'count' => count($results),
        'results' => $results,
    ]);
});
```

## CORS Headers

For cross-origin requests, add CORS headers:

```php
function corsResponse($data, $status = 200) {
    return new \Ava\Http\Response(
        json_encode($data),
        $status,
        [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-API-Key',
        ]
    );
}

// Handle OPTIONS preflight
$router->addRoute('/api/posts', function($request) {
    if ($request->method() === 'OPTIONS') {
        return new \Ava\Routing\RouteMatch(
            type: 'api',
            template: '__raw__',
            params: ['response' => corsResponse(null, 204)]
        );
    }
    
    // Normal GET handling...
});
```

## Response Helper

Add this helper function to your plugin:

```php
function jsonResponse($data, int $status = 200): \Ava\Routing\RouteMatch {
    return new \Ava\Routing\RouteMatch(
        type: 'api',
        template: '__raw__',
        params: ['response' => \Ava\Http\Response::json($data, $status)]
    );
}
```
