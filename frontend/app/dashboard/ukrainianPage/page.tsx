import Image from "next/image";
import UkrainianImg from "@/public/images/ukrainianday.jpeg";
import { getTranslations } from "next-intl/server";
import { getPageContent } from "@/lib/page-content";

export default async function UkrainianDayPage() {
  const t = await getTranslations("ukrainianDay");
  const cms = await getPageContent({ routePath: "/dashboard/ukrainianPage" });

  const title = cms.title || t("title");
  const heroImage = cms.images[0] ?? UkrainianImg;
  const paragraphs =
    cms.paragraphs.length > 0
      ? cms.paragraphs
      : [t("p1"), t("p2"), t("p3"), t("highlight"), t("p4"), t("closing")];

  return (
    <main className="relative w-full min-h-screen overflow-hidden bg-black text-white">
      <div className="relative h-screen w-full">
        <Image
          src={heroImage}
          alt="Ukrainian Day"
          fill
          priority
          className="object-cover object-center"
        />
        <div className="absolute inset-0 bg-black/50" />
        <div className="absolute bottom-10 left-6 max-w-3xl md:left-12">
          <h1 className="text-4xl font-extrabold uppercase tracking-wider sm:text-5xl md:text-6xl">
            {title}
          </h1>
          <p className="mt-2 text-sm opacity-80">{t("dateLine")}</p>
        </div>
      </div>

      <div className="mx-auto flex max-w-4xl flex-col gap-6 px-6 py-12 text-sm uppercase leading-relaxed md:text-base">
        {paragraphs.map((p, i) => (
          <p key={i} className="text-white/80">
            {p}
          </p>
        ))}
      </div>
    </main>
  );
}
