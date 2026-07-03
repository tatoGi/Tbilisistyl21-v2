<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()->latest()->get();

        return response()->json([
            'data' => $posts->map(fn (Post $post) => $this->summary($post))->all(),
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::published()
            ->whereRaw('trim(slug) = ?', [trim($slug)])
            ->firstOrFail();

        return response()->json(['data' => $this->detail($post)]);
    }

    /** Lightweight list shape: title, cover from first image block, optional excerpt. */
    private function summary(Post $post): array
    {
        $blocks = $this->effectiveBlocks($post);

        return [
            'id' => $post->id,
            'slug' => trim($post->slug),
            'title' => $post->getTranslations('title'),
            'status' => $post->status,
            'featured' => (bool) $post->featured,
            'created_at' => $post->created_at?->toIso8601String(),
            'cover' => $this->extractCover($blocks),
            'excerpt' => $this->extractExcerpt($blocks),
        ];
    }

    /** Full post shape including resolved content blocks. */
    private function detail(Post $post): array
    {
        $blocks = $this->effectiveBlocks($post);

        return array_merge($this->summary($post), [
            'content_blocks' => $this->resolveBlocks($blocks),
        ]);
    }

    /**
     * Use stored blocks, or synthesize a richText block from legacy `body`
     * so older migrated posts still render on the frontend.
     */
    private function effectiveBlocks(Post $post): array
    {
        $blocks = $post->content_blocks ?? [];
        if ($blocks !== []) {
            return $blocks;
        }

        $body = $post->getTranslations('body');
        if (array_filter($body, fn ($v) => is_string($v) && trim($v) !== '')) {
            return [['type' => 'richText', 'data' => ['content' => $body]]];
        }

        return [];
    }

    /** First image path from image, hero, or gallery blocks (for list thumbnails). */
    private function extractCover(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];

            if (in_array($type, ['image', 'hero'], true) && !empty($data['image'])) {
                return $this->mediaUrl($data['image']);
            }

            if ($type === 'gallery' && !empty($data['images']) && is_array($data['images'])) {
                $first = $data['images'][0]['image'] ?? null;
                if ($first) {
                    return $this->mediaUrl($first);
                }
            }
        }

        return null;
    }

    /** Plain-text excerpt from the first richText block (all locales). */
    private function extractExcerpt(array $blocks): array
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') !== 'richText') {
                continue;
            }
            $content = $block['data']['content'] ?? null;
            if (!is_array($content)) {
                continue;
            }
            $excerpt = [];
            foreach ($content as $locale => $text) {
                if (!is_string($text) || trim($text) === '') {
                    continue;
                }
                $plain = preg_replace('/\s+/', ' ', trim($text)) ?? '';
                $excerpt[$locale] = mb_strlen($plain) > 160 ? mb_substr($plain, 0, 157) . '…' : $plain;
            }
            if ($excerpt !== []) {
                return $excerpt;
            }
        }

        return [];
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
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
