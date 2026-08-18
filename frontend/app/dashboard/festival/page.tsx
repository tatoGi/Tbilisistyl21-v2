import { getTranslations } from "next-intl/server";
import { getFestivalHeroContent } from "@/lib/festival-landing";
import { getFeaturedPages, getFeaturedPartners, getFeaturedNews } from "@/lib/nav";
import { listProducts } from "@/lib/products";
import { getDjVoteState } from "@/lib/dj-vote";
import { getCurrentLocale } from "@/lib/locale";
import FestivalHero from "../../components/festival/FestivalHero";
import DjVote from "../../components/festival/DjVote";
import ProductReel from "../../components/ProductReel";
import PartnersStrip from "../../components/PartnersStrip";
import NewsTeaser from "../../components/NewsTeaser";

export const dynamic = "force-dynamic";

export default async function FestivalPage() {
  const [hero, featured, products, partners, featuredNews, djVote, locale, t] = await Promise.all([
    getFestivalHeroContent(),
    getFeaturedPages(),
    listProducts({ publicOnly: true }),
    getFeaturedPartners(),
    getFeaturedNews(6),
    getDjVoteState(),
    getCurrentLocale(),
    getTranslations(),
  ]);

  return (
    <main className="relative w-full bg-black">
      <FestivalHero
        imageUrl={hero.imageUrl}
        pages={featured}
      />

      <DjVote initial={djVote} locale={locale} />

      <ProductReel products={products} />

      {partners.length > 0 ? (
        <PartnersStrip partners={partners} heading={t("nav.partners")} viewAllLabel={t("common.viewAll")} />
      ) : null}

      {featuredNews.length > 0 ? (
        <NewsTeaser posts={featuredNews} heading={t("nav.news")} viewAllLabel={t("common.viewAll")} />
      ) : null}
    </main>
  );
}
