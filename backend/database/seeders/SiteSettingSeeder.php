<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::set('hero', [
            'heading' => [
                'ka' => 'TBILISI STYLE 21', 'en' => 'TBILISI STYLE 21',
                'ru' => 'TBILISI STYLE 21', 'ua' => 'TBILISI STYLE 21',
            ],
            'subheading' => [
                'ka' => 'მუსიკისა და კულტურის ფესტივალი',
                'en' => 'A festival of music and culture',
                'ru' => 'Фестиваль музыки и культуры',
                'ua' => 'Фестиваль музики та культури',
            ],
        ]);

        SiteSetting::set('instagramUrl', 'https://www.instagram.com/tbilisistyle21/');
        SiteSetting::set('tiktokUrl', 'https://www.tiktok.com/@tbilisistyle21');

        SiteSetting::set('contact', [
            'phone' => '+995 599 99 99 99',
            'phoneHref' => '+995599999999',
            'email' => 'info@tbilisistyle21.ge',
            'address' => null,
        ]);
    }
}
