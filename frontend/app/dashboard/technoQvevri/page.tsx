import Image from "next/image";
import TechnoRaveImg from "@/public/images/technoqvevri.jpeg";
import { getTranslations } from "next-intl/server";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function TechnoRavePage() {
  const t = await getTranslations("technoQvevri");
  const cms = await getPageContent({ routePath: "/dashboard/technoQvevri" });

  const title = cms.title || t("title");
  const heroImage = cms.images[0] ?? TechnoRaveImg;
  const fallback = [
    t("p1"), t("p2"), t("p3"), t("p4"), t("p5"), t("p6"), t("p7"), t("p8"),
    t("p9"), t("p10"),
  ];
  const paragraphs = cms.paragraphs.length > 0 ? cms.paragraphs : fallback;

  return (
    <ContentPageLayout
      title={title}
      subtitle={t("date")}
      eyebrow="Tbilisi Style 21"
      heroImage={heroImage}
      contentWidth="wide"
    >
      <div className="mx-auto flex max-w-md justify-center">
        <div className="overflow-hidden rounded-2xl border border-white/10">
          <Image
            src={heroImage}
            alt="Techno Qvevri"
            width={768}
            height={768}
            className="h-auto w-full object-contain"
            priority
          />
        </div>
      </div>

      <ContentProse>
        {paragraphs.map((p, i) => (
          <p key={i}>{p}</p>
        ))}
        <p className="font-semibold text-white">
          {t("date")}
          <br />
          {t("eventName")}
        </p>
        <p className="font-semibold text-yellow-300">{t("ticketNote")}</p>
      </ContentProse>
    </ContentPageLayout>
  );
}
