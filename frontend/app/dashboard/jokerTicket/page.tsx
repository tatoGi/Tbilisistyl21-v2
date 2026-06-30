import Image from "next/image";
import joker1 from "@/public/images/joker1.jpeg";
import joker2 from "@/public/images/joker2.jpeg";
import { getTranslations } from "next-intl/server";
import ContentFigure from "../../components/content/ContentFigure";
import ContentPageLayout from "../../components/content/ContentPageLayout";
import ContentProse from "../../components/content/ContentProse";
import { getPageContent } from "@/lib/page-content";

export default async function JokerTicketPage() {
  const t = await getTranslations("jokerTicket");
  const tNav = await getTranslations("nav");
  const cms = await getPageContent({ routePath: "/dashboard/jokerTicket" });

  const title = cms.title || tNav("joker");
  const body = cms.texts[0] ?? t("body");
  const heroImage = cms.images[0] ?? joker1;
  const secondImage = cms.images[1] ?? joker2;

  return (
    <ContentPageLayout
      title={title}
      eyebrow="Tbilisi Style 21"
      heroImage={heroImage}
      contentWidth="wide"
    >
      <ContentFigure src={heroImage} alt="Joker Ticket" priority />

      <ContentProse>
        <p className="whitespace-pre-line">{body}</p>
      </ContentProse>

      <div className="mx-auto mt-10 max-w-sm">
        <div className="overflow-hidden rounded-2xl border border-white/10">
          <Image
            src={secondImage}
            alt="Joker"
            width={640}
            height={640}
            className="h-auto w-full object-cover"
          />
        </div>
      </div>
    </ContentPageLayout>
  );
}
