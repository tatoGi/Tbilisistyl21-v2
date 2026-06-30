import { getTranslations } from "next-intl/server";
import MainStage1 from "@/public/images/mainstage11.jpeg";
import MainStage2 from "@/public/images/mainstage22.jpeg";
import ContentFigure from "../../components/content/ContentFigure";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function MainStagePage() {
  const t = await getTranslations("mainStage");
  // Admin overrides (Pages → "main-stage"); falls back to i18n + static images.
  const cms = await getPageContent("main-stage");

  const title = cms.title || t("title");
  const paragraphs =
    cms.paragraphs.length > 0
      ? cms.paragraphs
      : [t("p1"), t("p2"), t("p3"), t("p4"), t("p5")];
  const heroImage = cms.images[0] ?? MainStage1;
  const secondImage = cms.images[1] ?? MainStage2;

  return (
    <ContentPageLayout
      title={title}
      eyebrow="Tbilisi Style 21"
      heroImage={heroImage}
      contentWidth="wide"
    >
      <ContentFigure src={heroImage} alt="Main Stage" priority />

      <ContentProse>
        {paragraphs.map((p, i) => (
          <p key={i}>{p}</p>
        ))}
      </ContentProse>

      <div className="mt-10">
        <ContentFigure src={secondImage} alt="Main Stage" />
      </div>
    </ContentPageLayout>
  );
}
