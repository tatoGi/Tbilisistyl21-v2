import { getTranslations } from "next-intl/server";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function RulesPage() {
  const t = await getTranslations("rulesAndTerms");
  const cms = await getPageContent({ routePath: "/dashboard/rulesAndTerms" });

  const title = cms.title || t("title");
  const body = cms.texts[0] ?? t("body");

  return (
    <ContentPageLayout title={title} eyebrow="Tbilisi Style 21">
      <ContentProse>
        <p className="whitespace-pre-line">{body}</p>
      </ContentProse>
    </ContentPageLayout>
  );
}
