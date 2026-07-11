import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { RenderBlocks } from "@/app/components/RenderBlocks";
import { getCurrentLocale } from "@/lib/locale";
import { getNewsPost, formatNewsDate } from "@/lib/posts";

export const dynamic = "force-dynamic";

type PageProps = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const post = await getNewsPost(slug);
  if (!post) return {};
  return { title: `${post.title} — Tbilisi Style 21` };
}

export default async function NewsPostPage({ params }: PageProps) {
  const { slug } = await params;
  const post = await getNewsPost(slug);
  if (!post) notFound();

  const locale = await getCurrentLocale();

  return (
    <main className="relative min-h-screen w-full overflow-hidden bg-[#0b0906] text-[color:var(--ts-body)]">
      <div className="pointer-events-none absolute left-1/2 top-[-200px] z-0 h-[600px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(232,184,75,0.22)_0%,transparent_70%)] opacity-35" />

      <article className="relative z-10 mx-auto w-full max-w-3xl px-5 pb-20 pt-28 md:px-8">
        <Link
          href="/news"
          className="text-xs font-bold uppercase tracking-[0.22em] text-[#e8b84b] transition hover:text-white"
        >
          ← News
        </Link>

        {post.publishedAt ? (
          <p className="mt-6 text-xs uppercase tracking-widest text-[color:var(--ts-muted)]">
            {formatNewsDate(post.publishedAt)}
          </p>
        ) : null}

        <h1 className="font-unbounded mt-3 text-[clamp(1.9rem,5vw,3.2rem)] font-extrabold tracking-[-0.01em] text-[color:var(--ts-head)]">
          {post.title}
        </h1>

        {post.blocks.length > 0 ? (
          <div className="mt-10">
            <RenderBlocks blocks={post.blocks} locale={locale} />
          </div>
        ) : null}
      </article>
    </main>
  );
}
