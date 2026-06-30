import { getTranslations } from "next-intl/server";
import foodzone1 from "@/public/images/foodzone1.jpeg";
import foodzone2 from "@/public/images/foodzone2.jpeg";
import ContentFigure from "../../components/content/ContentFigure";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function FoodAndBarsPage() {
  const t = await getTranslations("foodZone");
  const cms = await getPageContent("food-zone");

  const title = cms.title || t("title");
  const body = cms.texts[0] ?? t("body");
  const heroImage = cms.images[0] ?? foodzone1;
  const image1 = cms.images[0] ?? foodzone1;
  const image2 = cms.images[1] ?? foodzone2;

  return (
    <ContentPageLayout
      title={title}
      eyebrow="Tbilisi Style 21"
      heroImage={heroImage}
      contentWidth="wide"
    >
      <ContentProse>
        <p className="whitespace-pre-line">{body}</p>
      </ContentProse>

      <div className="mt-10 flex flex-col gap-5">
        <ContentFigure src={image1} alt="Food Zone 1" />
        <ContentFigure src={image2} alt="Food Zone 2" />
      </div>
    </ContentPageLayout>
  );
}
