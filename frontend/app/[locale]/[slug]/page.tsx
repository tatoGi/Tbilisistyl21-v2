import { getLocale } from 'next-intl/server';
import { getPage } from '@/lib/pages';
import { t } from '@/lib/utils';
import { notFound } from 'next/navigation';

export default async function DynamicPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const locale = await getLocale();

  // Avoid catching known routes
  const reserved = ['tickets', 'products', 'music', 'news', 'partners'];
  if (reserved.includes(slug)) {
    notFound();
  }

  let page;
  try {
    page = await getPage(slug, locale);
  } catch {
    notFound();
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">{t(page.title, locale)}</h1>

      {page.content_blocks && page.content_blocks.length > 0 && (
        <div className="prose prose-invert max-w-none">
          {page.content_blocks.map((block: unknown, i: number) => (
            <div key={i} className="mb-6">
              <pre className="text-sm text-gray-400">
                {JSON.stringify(block, null, 2)}
              </pre>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
