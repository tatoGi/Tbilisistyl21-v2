import { getTranslations } from "next-intl/server";
import Qvevri1 from "@/public/images/qvevriStage2.jpeg";
import Qvevri2 from "@/public/images/qvevriStage1.jpeg";
import ContentFigure from "../../components/content/ContentFigure";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function QvevriStagePage() {
  const t = await getTranslations("qvevriStage");
  const cms = await getPageContent({ routePath: "/dashboard/qvevriStage" });

  const title = cms.title || t("title");
  const paragraphs =
    cms.paragraphs.length > 0
      ? cms.paragraphs
      : [t("intro"), t("p1"), t("p2"), t("p3")];
  const heroImage = cms.images[0] ?? Qvevri1;
  const secondImage = cms.images[1] ?? Qvevri2;

  return (
    <ContentPageLayout
      title={title}
      eyebrow="Tbilisi Style 21"
      heroImage={heroImage}
      contentWidth="wide"
    >
      <ContentFigure src={heroImage} alt="Qvevri Stage" priority />

      <ContentProse>
        {paragraphs.map((p, i) => (
          <p key={i}>{p}</p>
        ))}
      </ContentProse>

      <div className="mt-10">
        <ContentFigure src={secondImage} alt="Qvevri Stage" />
      </div>
    </ContentPageLayout>
  );
}
