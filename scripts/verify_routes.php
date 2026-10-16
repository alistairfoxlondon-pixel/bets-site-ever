<?php

/**
 * Static route audit: every route in routes/web.php must point at a public
 * method that actually exists on the controller, and every view the routes
 * render must exist on disk.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = app('router')->getRoutes();

$errors = [];
$checked = 0;

foreach ($routes as $route) {
    $action = $route->getActionName();

    if (! str_contains($action, '@')) {
        continue;
    }

    [$class, $method] = explode('@', $action);
    $checked++;

    if (! class_exists($class)) {
        $errors[] = "Missing controller class {$class} for {$route->uri()}";

        continue;
    }

    if (! method_exists($class, $method)) {
        $errors[] = "{$class}::{$method}() missing for route {$route->uri()} [{$route->methods()[0]}]";

        continue;
    }

    $reflection = new ReflectionMethod($class, $method);

    if (! $reflection->isPublic()) {
        $errors[] = "{$class}::{$method}() is not public for route {$route->uri()}";
    }
}

// View existence check for every view referenced in the blade templates.
$viewRoot = base_path('resources/views');
$missingViews = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot));

foreach ($files as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());
    preg_match_all("/view\(\s*'([^']+)'/", $contents, $matches);

    foreach ($matches[1] as $viewName) {
        $path = $viewRoot.'/'.str_replace('.', '/', $viewName).'.blade.php';

        if (! file_exists($path)) {
            $missingViews[] = $viewName.' (referenced in '.$file->getFilename().')';
        }
    }
}

// Also check views returned from controllers by scanning for view('...') calls.
$controllerFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Controllers')));

foreach ($controllerFiles as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());
    preg_match_all("/view\(\s*'([^']+)'/", $contents, $matches);

    foreach ($matches[1] as $viewName) {
        $path = $viewRoot.'/'.str_replace('.', '/', $viewName).'.blade.php';

        if (! file_exists($path)) {
            $missingViews[] = $viewName.' (referenced in '.$file->getFilename().')';
        }
    }
}

echo "routes checked: {$checked}\n";

if ($errors) {
    echo "\nROUTE PROBLEMS:\n";
    foreach ($errors as $error) {
        echo ' - '.$error."\n";
    }
}

if ($missingViews) {
    echo "\nMISSING VIEWS:\n";
    foreach (array_unique($missingViews) as $view) {
        echo ' - '.$view."\n";
    }
}

if (! $errors && ! $missingViews) {
    echo "OK: every route resolves and every view exists\n";
}

exit($errors || $missingViews ? 1 : 0);
