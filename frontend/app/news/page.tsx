import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { listNews, formatNewsDate } from "@/lib/posts";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "News — Tbilisi Style 21",
};

export default async function NewsPage() {
  const posts = await listNews();

  return (
    <main className="relative mx-auto min-h-screen w-full max-w-4xl px-5 pb-16 pt-28 text-white md:px-10">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_75%)]" />

      <div className="relative z-10">
        <p className="font-heading text-xs font-bold uppercase tracking-[0.2em] text-yellow-300">
          Tbilisi Style 21
        </p>
        <h1 className="font-heading mt-2 text-[clamp(2rem,6vw,3.5rem)] font-extrabold uppercase tracking-tight">
          News
        </h1>

        {posts.length === 0 ? (
          <p className="mt-10 text-white/50">No news yet.</p>
        ) : (
          <ul className="mt-10 grid gap-4">
            {posts.map((post) => (
              <li key={post.id}>
                <Link
                  href={`/news/${post.slug}`}
                  className="group flex gap-5 overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition hover:border-yellow-300/40 hover:bg-white/[0.08] sm:items-center"
                >
                  <div className="relative hidden aspect-[16/9] w-36 shrink-0 overflow-hidden bg-white/[0.02] sm:block md:w-44">
                    {post.coverUrl ? (
                      <Image
                        src={post.coverUrl}
                        alt=""
                        fill
                        className="object-cover transition duration-500 group-hover:scale-105"
                        sizes="176px"
                      />
                    ) : (
                      <div className="flex h-full w-full items-center justify-center text-[9px] font-bold uppercase tracking-[0.2em] text-white/20">
                        TS21
                      </div>
                    )}
                  </div>
                  <div className="flex flex-1 flex-col p-6">
                    {post.publishedAt ? (
                      <span className="block text-xs uppercase tracking-widest text-yellow-300/70">
                        {formatNewsDate(post.publishedAt)}
                      </span>
                    ) : null}
                    <span className="font-heading mt-1 block text-xl font-bold uppercase tracking-wide text-white transition group-hover:text-yellow-300">
                      {post.title}
                    </span>
                    {post.excerpt ? (
                      <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-white/50">
                        {post.excerpt}
                      </p>
                    ) : null}
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </div>
    </main>
  );
}
