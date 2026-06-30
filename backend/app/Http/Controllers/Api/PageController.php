<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    /**
     * List published pages. Supports `?nav=1` (menu pages, ordered) and
     * `?featured=1` (homepage-featured pages). Without filters, returns all
     * published pages. Heavy `content_blocks` are omitted from list responses.
     */
    public function index(Request $request)
    {
        $query = Page::published();

        if ($request->boolean('nav')) {
            $query->where('show_in_nav', true);
        }

        if ($request->boolean('featured')) {
            $query->where('featured_on_home', true);
        }

        $pages = $query->orderBy('nav_order')->get();

        return response()->json([
            'data' => $pages->map(fn (Page $page) => $this->summary($page))->all(),
        ]);
    }

    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (!$page) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['data' => $this->detail($page)]);
    }

    /** Lightweight shape for menus / featured lists (no content blocks). */
    private function summary(Page $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'route_path' => $page->route_path,
            'show_in_nav' => (bool) $page->show_in_nav,
            'nav_order' => (int) $page->nav_order,
            'featured_on_home' => (bool) $page->featured_on_home,
            'title' => $page->getTranslations('title'),
            'nav_label' => $page->getTranslations('nav_label'),
        ];
    }

    /** Full page shape including resolved content blocks. */
    private function detail(Page $page): array
    {
        return array_merge($this->summary($page), [
            'is_published' => (bool) $page->is_published,
            'content_blocks' => $this->resolveBlocks($page->content_blocks ?? []),
        ]);
    }

    /** Convert stored image paths inside blocks into absolute /storage URLs. */
    private function resolveBlocks(array $blocks): array
    {
        return array_map(function ($block) {
            $data = $block['data'] ?? [];

            if (isset($data['image'])) {
                $data['image'] = $this->mediaUrl($data['image']);
            }

            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_map(function ($item) {
                    if (isset($item['image'])) {
                        $item['image'] = $this->mediaUrl($item['image']);
                    }
                    return $item;
                }, $data['images']);
            }

            $block['data'] = $data;
            return $block;
        }, $blocks);
    }

    private function mediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        // Already a URL or an absolute path (e.g. a seeded "/images/..." that the
        // frontend serves from its own public dir, or a "/storage/..." path).
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }
        // A relative disk path from an admin upload (e.g. "page-media/x.jpg").
        return Storage::disk('public')->url($path);
    }
}
