<?php

declare(strict_types=1);

namespace App\Core;

use App\Storage\CompanyRepository;
use App\Storage\ContentRepository;
use App\Storage\JsonStore;
use App\Storage\MediaRepository;
use App\Storage\ProductRepository;
use App\Storage\SeoRepository;
use App\Support\Assets;
use App\Support\Seo;

/**
 * The application container.
 *
 * Small and explicit: every service is constructed once, lazily, with its
 * dependencies passed in. There is no autowiring to reason about.
 */
final class App
{
    private static ?self $instance = null;

    private ?JsonStore $store = null;
    private ?ProductRepository $products = null;
    private ?CompanyRepository $company = null;
    private ?ContentRepository $content = null;
    private ?SeoRepository $seoConfig = null;
    private ?MediaRepository $media = null;
    private ?Seo $seo = null;
    private ?Assets $assets = null;
    private ?View $view = null;

    private function __construct(
        public readonly Request $request,
        public readonly Locale $locale,
    ) {
    }

    public static function boot(Request $request): self
    {
        return self::$instance = new self($request, Locale::resolve($request));
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been booted.');
        }

        return self::$instance;
    }

    public function store(): JsonStore
    {
        return $this->store ??= new JsonStore();
    }

    public function products(): ProductRepository
    {
        return $this->products ??= new ProductRepository($this->store());
    }

    public function company(): CompanyRepository
    {
        return $this->company ??= new CompanyRepository($this->store());
    }

    public function content(): ContentRepository
    {
        return $this->content ??= new ContentRepository($this->store());
    }

    public function media(): MediaRepository
    {
        return $this->media ??= new MediaRepository($this->store());
    }

    public function seoConfig(): SeoRepository
    {
        return $this->seoConfig ??= new SeoRepository($this->store());
    }

    public function seo(): Seo
    {
        return $this->seo ??= new Seo($this->seoConfig(), $this->company());
    }

    public function assets(): Assets
    {
        return $this->assets ??= new Assets(APP_ROOT . '/public');
    }

    public function view(): View
    {
        if ($this->view === null) {
            $this->view = new View();
            $this->view->share([
                'app' => $this,
                'locale' => $this->locale,
                'company' => $this->company(),
                'assets' => $this->assets(),
                'media' => $this->media(),
            ]);
        }

        return $this->view;
    }

    /** Build a URL on the current site, preserving the language choice. */
    public function url(string $path, array $query = []): string
    {
        if ($this->locale->explicitChoice) {
            $query['lang'] = $this->locale->language;
        }

        $path = '/' . ltrim($path, '/');
        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query);
    }

    /** The same page in the other language. */
    public function languageToggleUrl(): string
    {
        $query = $this->request->query;
        $query['lang'] = $this->locale->other();

        return ($this->request->path === '/' ? '/' : $this->request->path)
            . '?' . http_build_query($query);
    }
}
