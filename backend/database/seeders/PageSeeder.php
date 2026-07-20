<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    private const LOCALES = ['ka', 'en', 'ru', 'ua'];

    /** Cached decoded message files, keyed by locale. */
    private array $messages = [];

    /**
     * Seeds the festival menu and each page's content blocks. Menu metadata
     * (labels, order, route) and the page copy are mirrored from the old
     * frontend, but now live in the DB and are fully editable from the admin.
     */
    public function run(): void
    {
        $this->loadMessages();

        $order = 10;
        foreach ($this->menu() as $entry) {
            Page::updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    'title' => $entry['label'],
                    'nav_label' => $entry['label'],
                    'route_path' => $entry['route'],
                    'show_in_nav' => true,
                    'show_in_footer' => in_array($entry['slug'], ['contact-us', 'rules-and-terms'], true),
                    'nav_order' => $order,
                    'footer_order' => match ($entry['slug']) {
                        'contact-us' => 10,
                        'rules-and-terms' => 20,
                        default => 100,
                    },
                    'featured_on_home' => $entry['featured'] ?? false,
                    'is_published' => true,
                    'content_blocks' => $this->buildBlocks($entry['slug']),
                ]
            );
            $order += 10;
        }
    }

    private function loadMessages(): void
    {
        foreach (self::LOCALES as $locale) {
            $path = database_path("seeders/content/{$locale}.json");
            $this->messages[$locale] = is_file($path)
                ? (json_decode(file_get_contents($path), true) ?: [])
                : [];
        }
    }

    /** Build the content blocks for a slug from its config (text + images). */
    private function buildBlocks(string $slug): array
    {
        $config = $this->contentConfig()[$slug] ?? null;
        if (!$config) {
            return [];
        }

        if (!empty($config['contact'])) {
            return [['type' => 'contact', 'data' => ['showPayments' => true]]];
        }

        $blocks = [];

        foreach ($config['images'] ?? [] as $src) {
            $data = ['image' => $src, 'width' => 'full'];
            if (isset($config['imageFit'])) {
                $data['fit'] = $config['imageFit'];
            }
            $blocks[] = ['type' => 'image', 'data' => $data];
        }

        $ns = $config['ns'] ?? null;
        if ($ns) {
            if (isset($config['body'])) {
                $content = $this->localized($ns, $config['body']);
            } else {
                $content = $this->joinParagraphs($ns, $config['paras'] ?? []);
            }
            if (array_filter($content)) {
                $blocks[] = ['type' => 'richText', 'data' => ['content' => $content]];
            }
        }

        return $blocks;
    }

    /** Localized value for a single message key: ['ka'=>..,'en'=>..,..]. */
    private function localized(string $ns, string $key): array
    {
        $out = [];
        foreach (self::LOCALES as $locale) {
            $out[$locale] = $this->messages[$locale][$ns][$key] ?? '';
        }
        return $out;
    }

    /** Join several message keys per locale into one rich-text string. */
    private function joinParagraphs(string $ns, array $keys): array
    {
        $out = [];
        foreach (self::LOCALES as $locale) {
            $parts = [];
            foreach ($keys as $key) {
                $value = $this->messages[$locale][$ns][$key] ?? '';
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
            $out[$locale] = implode("\n\n", $parts);
        }
        return $out;
    }

    /**
     * Per-page content: which i18n namespace/keys become the rich text and
     * which festival assets become image blocks. The frontend pages read these
     * back (with the old i18n + static images as a fallback), so the design is
     * unchanged while the content is now CMS-managed.
     */
    private function contentConfig(): array
    {
        return [
            'main-stage' => [
                'ns' => 'mainStage',
                'paras' => ['p1', 'p2', 'p3', 'p4', 'p5'],
                'images' => ['/images/mainstage11.jpeg', '/images/mainstage22.jpeg'],
            ],
            'qvevri-stage' => [
                'ns' => 'qvevriStage',
                'paras' => ['intro', 'p1', 'p2', 'p3'],
                'images' => ['/images/qvevriStage2.jpeg', '/images/qvevriStage1.jpeg'],
            ],
            'techno-qvevri' => [
                'ns' => 'technoQvevri',
                'paras' => ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9', 'p10'],
                'images' => ['/images/technoqvevri.jpeg'],
            ],
            'lineup' => [
                // Poster artwork: never crop to the 16:9 banner.
                'imageFit' => 'full',
                'images' => [
                    '/lineups/tbilisistyleday1.jpeg',
                    '/lineups/tbilisistyleday2.jpeg',
                    '/lineups/tbilisistyleday3.jpeg',
                    '/lineups/rave.jpeg',
                ],
            ],
            'joker-ticket' => [
                'ns' => 'jokerTicket',
                'body' => 'body',
                'images' => ['/images/joker1.jpeg', '/images/joker2.jpeg'],
            ],
            'ukrainian-day' => [
                'ns' => 'ukrainianDay',
                'paras' => ['p1', 'p2', 'p3', 'highlight', 'p4', 'closing'],
                'images' => ['/images/ukrainianday.jpeg'],
            ],
            'four-stages' => [
                'ns' => 'fourStages',
                'body' => 'body',
            ],
            'our-story' => [
                'ns' => 'ourStory',
                'body' => 'body',
            ],
            'mission' => [
                'ns' => 'mission',
                'paras' => ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'signature'],
                'images' => ['/images/mission.jpeg'],
            ],
            'food-zone' => [
                'ns' => 'foodZone',
                'body' => 'body',
                'images' => ['/images/foodzone1.jpeg', '/images/foodzone2.jpeg'],
            ],
            'rules-and-terms' => [
                'ns' => 'rulesAndTerms',
                'body' => 'body',
            ],
            'contact-us' => [
                'contact' => true,
            ],
            // contact-us contact block uses Site settings for phone/email.
        ];
    }

    /** Menu labels for the four locales, mirroring the old frontend nav. */
    private function menu(): array
    {
        return [
            ['slug' => 'main-stage', 'route' => '/dashboard/mainStage', 'featured' => true, 'label' => [
                'ka' => 'მთავარი სცენა', 'en' => 'MAIN STAGE', 'ru' => 'ГЛАВНАЯ СЦЕНА', 'ua' => 'ГОЛОВНА СЦЕНА',
            ]],
            ['slug' => 'qvevri-stage', 'route' => '/dashboard/qvevriStage', 'featured' => true, 'label' => [
                'ka' => 'ქვევრი', 'en' => 'QVEVRI', 'ru' => 'КВЕВРИ', 'ua' => 'КВЕВРІ',
            ]],
            ['slug' => 'techno-qvevri', 'route' => '/dashboard/technoQvevri', 'label' => [
                'ka' => 'ტექნო ქვევრი', 'en' => 'TECHNO QVEVRI', 'ru' => 'ТЕХНО КВЕВРИ', 'ua' => 'ТЕХНО КВЕВРІ',
            ]],
            ['slug' => 'lineup', 'route' => '/dashboard/lineUp', 'featured' => true, 'label' => [
                'ka' => 'ლაინაფი', 'en' => 'LINEUP', 'ru' => 'ЛАЙНАП', 'ua' => 'ЛАЙНАП',
            ]],
            ['slug' => 'joker-ticket', 'route' => '/dashboard/jokerTicket', 'label' => [
                'ka' => 'ჯოკერი', 'en' => 'JOKER', 'ru' => 'ДЖОКЕР', 'ua' => 'ДЖОКЕР',
            ]],
            ['slug' => 'ukrainian-day', 'route' => '/dashboard/ukrainianPage', 'label' => [
                'ka' => 'უკრაინული დღე', 'en' => 'UKRAINIAN DAY', 'ru' => 'УКРАИНСКИЙ ДЕНЬ', 'ua' => 'УКРАЇНСЬКИЙ ДЕНЬ',
            ]],
            ['slug' => 'four-stages', 'route' => '/dashboard/fourStages', 'label' => [
                'ka' => '4 სცენა', 'en' => '4 STAGES', 'ru' => '4 СЦЕНЫ', 'ua' => '4 СЦЕНИ',
            ]],
            ['slug' => 'our-story', 'route' => '/dashboard/ourStory', 'label' => [
                'ka' => 'ჩვენი ისტორია', 'en' => 'OUR STORY', 'ru' => 'НАША ИСТОРИЯ', 'ua' => 'НАША ІСТОРІЯ',
            ]],
            ['slug' => 'tickets', 'route' => '/dashboard/tickets', 'label' => [
                'ka' => 'ბილეთი', 'en' => 'TICKET', 'ru' => 'БИЛЕТ', 'ua' => 'КВИТОК',
            ]],
            ['slug' => 'shop', 'route' => '/dashboard/shop', 'label' => [
                'ka' => 'მაღაზია', 'en' => 'SHOP', 'ru' => 'МАГАЗИН', 'ua' => 'МАГАЗИН',
            ]],
            ['slug' => 'mission', 'route' => '/dashboard/mission', 'label' => [
                'ka' => 'მისია', 'en' => 'MISSION', 'ru' => 'МИССИЯ', 'ua' => 'МІСІЯ',
            ]],
            ['slug' => 'food-zone', 'route' => '/dashboard/foodZone', 'label' => [
                'ka' => 'საკვების ზონა', 'en' => 'FOOD ZONE', 'ru' => 'ФУД-ЗОНА', 'ua' => 'ФУД-ЗОНА',
            ]],
            ['slug' => 'contact-us', 'route' => '/dashboard/contactUs', 'label' => [
                'ka' => 'დაგვიკავშირდით', 'en' => 'CONTACT US', 'ru' => 'СВЯЖИТЕСЬ С НАМИ', 'ua' => "ЗВ'ЯЖІТЬСЯ З НАМИ",
            ]],
            ['slug' => 'rules-and-terms', 'route' => '/dashboard/rulesAndTerms', 'label' => [
                'ka' => 'ფესტივალის წესები და პირობები', 'en' => 'FESTIVAL RULES & TERMS', 'ru' => 'ПРАВИЛА И УСЛОВИЯ ФЕСТИВАЛЯ', 'ua' => 'ПРАВИЛА ТА УМОВИ ФЕСТИВАЛЮ',
            ]],
        ];
    }
}
