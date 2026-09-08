<?php

declare(strict_types=1);

/**
 * Front controller.
 *
 * Every request that is not a static file reaches this file through the
 * rewrite rule in .htaccess.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Locale;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Http\HomeController;
use App\Storage\StoreException;

$request = Request::fromGlobals();
$app = App::boot($request);

// Remember an explicit language choice so the visitor keeps it while browsing,
// including across the .ir / .com pair where each domain has its own default.
$chosen = strtolower($request->queryString('lang'));
if (in_array($chosen, Locale::SUPPORTED, true) && !headers_sent()) {
    setcookie(Locale::COOKIE, $chosen, [
        'expires' => time() + 31_536_000,
        'path' => '/',
        'secure' => $request->secure,
        'httponly' => false, // read by the language toggle in the client
        'samesite' => 'Lax',
    ]);
}

$router = new Router();

$home = new HomeController($app);

$router->get('/', $home->index(...));
$router->fallback($home->notFound(...));

try {
    $response = $router->dispatch($request);
} catch (StoreException $e) {
    error_log('[store] ' . $e->getMessage());
    $response = $home->serviceUnavailable();
} catch (\Throwable $e) {
    error_log('[error] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!Config::isProduction()) {
        throw $e;
    }
    $response = $home->serverError();
}

$response->send();
