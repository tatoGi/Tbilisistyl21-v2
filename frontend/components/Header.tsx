'use client';

import { useLocale, useTranslations } from 'next-intl';
import { Link, usePathname, useRouter } from '@/i18n/navigation';
import { routing } from '@/i18n/routing';

export function Header() {
  const t = useTranslations('common');
  const locale = useLocale();
  const pathname = usePathname();
  const router = useRouter();

  function switchLocale(newLocale: string) {
    router.replace(pathname, { locale: newLocale });
  }

  return (
    <header className="border-b border-border bg-surface sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <Link href="/" className="text-2xl font-bold text-primary">
          TbilisiStyle21
        </Link>

        <nav className="hidden md:flex items-center gap-6">
          <Link href="/" className="hover:text-primary transition-colors">
            {t('home')}
          </Link>
          <Link href="/tickets" className="hover:text-primary transition-colors">
            {t('tickets')}
          </Link>
          <Link href="/products" className="hover:text-primary transition-colors">
            {t('products')}
          </Link>
          <Link href="/music" className="hover:text-primary transition-colors">
            {t('music')}
          </Link>
          <Link href="/news" className="hover:text-primary transition-colors">
            {t('news')}
          </Link>
          <Link href="/partners" className="hover:text-primary transition-colors">
            {t('partners')}
          </Link>
        </nav>

        <div className="flex items-center gap-2">
          {routing.locales.map((loc) => (
            <button
              key={loc}
              onClick={() => switchLocale(loc)}
              className={`px-2 py-1 text-sm rounded ${
                locale === loc
                  ? 'bg-primary text-black font-bold'
                  : 'text-gray-400 hover:text-white'
              }`}
            >
              {loc.toUpperCase()}
            </button>
          ))}
        </div>
      </div>
    </header>
  );
}
