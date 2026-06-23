import { getLocale } from 'next-intl/server';
import { listPosts } from '@/lib/posts';
import { t } from '@/lib/utils';
import type { Post } from '@/lib/types';

export default async function NewsPage() {
  const locale = await getLocale();
  let posts: Post[] = [];
  try {
    posts = await listPosts(locale);
  } catch {
    // API unavailable
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">
        {locale === 'ka' ? 'სიახლეები' : 'News'}
      </h1>

      {posts.length === 0 ? (
        <p className="text-gray-400">
          {locale === 'ka' ? 'სიახლეები არ მოიძებნა' : 'No news yet'}
        </p>
      ) : (
        <div className="space-y-6">
          {posts.map((post) => (
            <article
              key={post.id}
              className="bg-surface-light border border-border rounded-lg p-6"
            >
              <h2 className="text-xl font-semibold mb-2">
                {t(post.title, locale)}
              </h2>
              <time className="text-gray-500 text-sm block mb-4">
                {new Date(post.created_at).toLocaleDateString(locale)}
              </time>
              {post.body && (
                <div className="text-gray-300 leading-relaxed">
                  {t(post.body, locale)}
                </div>
              )}
            </article>
          ))}
        </div>
      )}
    </div>
  );
}
