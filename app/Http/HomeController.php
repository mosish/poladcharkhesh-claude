<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Families;
use App\Domain\Industries;
use App\Domain\Product;

final class HomeController
{
    public function __construct(private readonly App $app)
    {
    }

    public function index(Request $request): Response
    {
        $locale = $this->app->locale;
        $products = $this->app->products();
        $content = $this->app->content();
        $seo = $this->app->seo();

        $familyCounts = $products->familyCounts();
        $featured = $products->featured(6);

        // Facts shown in the hero and the capability strip are derived from the
        // catalogue itself or from the company record. Nothing is asserted that
        // the data cannot support — no years in business, no client counts, no
        // certification claims.
        $sourced = count(array_filter(
            $products->all(),
            static fn (Product $p): bool => $p->hasVerifiedSource()
        ));

        $facts = [
            [
                'value' => (string) $products->count(),
                'label' => $locale->t('facts.referencesLabel'),
            ],
            [
                'value' => (string) count($familyCounts),
                'label' => $locale->t('facts.familiesLabel'),
            ],
            [
                'value' => $sourced . '/' . $products->count(),
                'label' => $locale->t('facts.sourcedLabel'),
            ],
        ];

        // Deliberately three, and deliberately all numeric. The working hours
        // already sit in the utility bar, and a text value set in the figure
        // typeface reads as a broken number rather than a fact.

        $meta = $seo->meta($locale);
        $canonical = $seo->canonical($locale->language, '/');

        $body = $this->app->view()->page('pages/home', [
            'meta' => $meta,
            'canonical' => $canonical,
            'alternates' => $seo->alternates('/'),
            'bodyClass' => 'page-home',
            'schemas' => [
                $seo->organizationSchema($locale),
                $seo->breadcrumbSchema(
                    [['name' => $locale->t('nav.home'), 'path' => '/']],
                    $locale->language
                ),
            ],
            'content' => $content,
            'products' => $products,
            'featured' => $featured,
            'familyCounts' => $familyCounts,
            'facts' => $facts,
            'industries' => Industries::all($locale->language),
            'searchIndex' => $this->searchIndex(),
            'heroBearing' => $this->visualisable($featured, ['deep-groove-ball']),
            'instrumentBearing' => $this->visualisable($featured, ['spherical-roller', 'deep-groove-ball']),
        ]);

        return Response::html($body);
    }

    /**
     * Pick a bearing the visualiser can actually draw.
     *
     * The renderer needs complete principal dimensions and a rolling-element
     * family; a housing or an oil seal has no raceway to show. Preferred
     * families are tried in order, then any drawable product, and the caller
     * gets null if the catalogue holds nothing suitable — in which case the
     * visual is simply not rendered rather than faked.
     *
     * @param list<Product> $preferredPool
     * @param list<string>  $families
     */
    private function visualisable(array $preferredPool, array $families): ?Product
    {
        $drawable = static fn (Product $p): bool =>
            $p->d !== null && $p->D !== null && $p->B !== null
            && $p->D > $p->d
            && Families::isCalculable($p->family)
            && !in_array($p->family, ['bearing-housing', 'oil-seal', 'lubricant'], true);

        foreach ($families as $family) {
            foreach ($preferredPool as $product) {
                if ($product->family === $family && $drawable($product)) {
                    return $product;
                }
            }
            foreach ($this->app->products()->all() as $product) {
                if ($product->family === $family && $drawable($product)) {
                    return $product;
                }
            }
        }

        foreach ($this->app->products()->all() as $product) {
            if ($drawable($product)) {
                return $product;
            }
        }

        return null;
    }

    /**
     * A compact index for the hero's type-ahead. Only the fields the suggestion
     * list actually renders are sent, which keeps the payload around 8 KB for
     * the full catalogue — small enough to inline rather than fetch.
     *
     * @return list<array<string,string>>
     */
    private function searchIndex(): array
    {
        $locale = $this->app->locale;
        $out = [];

        foreach ($this->app->products()->all() as $product) {
            $out[] = [
                'code' => $product->designation(),
                'name' => $product->name($locale),
                'slug' => $product->slug,
                'family' => Families::label($product->family, $locale->language, true),
                'dims' => $product->dimensionSummary() ?? '',
                'url' => $this->app->url($product->url()),
            ];
        }

        return $out;
    }

    public function notFound(Request $request): Response
    {
        $locale = $this->app->locale;
        $seo = $this->app->seo();

        $body = $this->app->view()->page('pages/message', [
            'meta' => $seo->meta($locale, ['title' => $locale->t('state.notFoundTitle')]),
            'canonical' => '',
            'alternates' => [],
            'schemas' => [],
            'bodyClass' => 'page-message',
            'heading' => $locale->t('state.notFoundTitle'),
            'message' => $locale->t('state.notFoundBody'),
            'statusCode' => '404',
            'requestedPath' => $request->path,
        ]);

        return Response::html($body, 404);
    }

    public function serviceUnavailable(): Response
    {
        $locale = $this->app->locale;

        $body = $this->app->view()->page('pages/message', [
            'meta' => ['title' => $locale->t('state.unavailableTitle'), 'description' => ''],
            'canonical' => '',
            'alternates' => [],
            'schemas' => [],
            'bodyClass' => 'page-message',
            'heading' => $locale->t('state.unavailableTitle'),
            'message' => $locale->t('state.unavailableBody'),
            'statusCode' => '503',
            'requestedPath' => '',
        ]);

        return Response::html($body, 503)->withHeader('Retry-After', '120');
    }

    public function serverError(): Response
    {
        $locale = $this->app->locale;

        $body = $this->app->view()->page('pages/message', [
            'meta' => ['title' => $locale->t('state.serverErrorTitle'), 'description' => ''],
            'canonical' => '',
            'alternates' => [],
            'schemas' => [],
            'bodyClass' => 'page-message',
            'heading' => $locale->t('state.serverErrorTitle'),
            'message' => $locale->t('state.serverErrorBody'),
            'statusCode' => '500',
            'requestedPath' => '',
        ]);

        return Response::html($body, 500);
    }
}
