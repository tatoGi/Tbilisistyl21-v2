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
    <main className="relative mx-auto min-h-screen w-full max-w-6xl px-5 pb-16 pt-28 text-white md:px-10">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_75%)]" />

      <article className="relative z-10">
        <Link
          href="/news"
          className="text-xs font-bold uppercase tracking-[0.2em] text-yellow-300 transition hover:text-yellow-200"
        >
          ← News
        </Link>

        {post.publishedAt ? (
          <p className="mt-6 text-xs uppercase tracking-widest text-white/50">
            {formatNewsDate(post.publishedAt)}
          </p>
        ) : null}

        <h1 className="font-heading mt-2 text-[clamp(1.8rem,5vw,3rem)] font-extrabold uppercase tracking-tight">
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
