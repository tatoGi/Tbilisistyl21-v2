import type { Metadata } from "next";
import { listNews } from "@/lib/posts";
import NewsCardGrid from "@/app/components/NewsCardGrid";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "News — Tbilisi Style 21",
};

export default async function NewsPage() {
  const posts = await listNews();

  return (
    <main className="relative min-h-screen w-full overflow-hidden bg-black px-6 pb-20 pt-28 text-white md:pb-28 md:pt-32">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_70%)]" />

      <div className="relative z-10 mx-auto w-full max-w-6xl">
        <div className="mb-12 flex flex-col gap-1">
          <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-300">
            Tbilisi Style 21
          </p>
          <h1 className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white md:text-3xl">
            News
          </h1>
        </div>

        {posts.length === 0 ? (
          <p className="text-white/50">No news yet.</p>
        ) : (
          <NewsCardGrid posts={posts} />
        )}
      </div>
    </main>
  );
}
