import Image from "next/image";
import MissionImg from "@/public/images/mission.jpeg";
import { getTranslations } from "next-intl/server";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function MissionVisionPage() {
  const t = await getTranslations("mission");
  const cms = await getPageContent({ routePath: "/dashboard/mission" });

  const title = cms.title || t("title");
  const image = cms.images[0] ?? MissionImg;
  const fallback = [
    t("p1"), t("p2"), t("p3"), t("p4"), t("p5"), t("p6"), t("p7"), t("signature"),
  ];
  const paragraphs = cms.paragraphs.length > 0 ? cms.paragraphs : fallback;

  return (
    <ContentPageLayout
      title={title}
      subtitle={t("subtitle")}
      eyebrow="Tbilisi Style 21"
      contentWidth="wide"
    >
      <div className="flex flex-col gap-12 lg:flex-row lg:items-start">
        <div className="mx-auto w-full max-w-sm shrink-0 lg:mx-0 lg:max-w-xs">
          <div className="overflow-hidden rounded-2xl border border-white/10">
            <Image
              src={image}
              alt="Mission"
              width={640}
              height={640}
              className="h-auto w-full object-contain"
              priority
            />
          </div>
        </div>

        <ContentProse>
          {paragraphs.map((p, i) => (
            <p key={i}>{p}</p>
          ))}
        </ContentProse>
      </div>
    </ContentPageLayout>
  );
}
