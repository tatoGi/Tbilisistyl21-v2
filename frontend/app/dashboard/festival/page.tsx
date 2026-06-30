import AboutImg from "@/public/images/secondImg_1920x1080.jpeg";
import { getTranslations } from "next-intl/server";
import { getFestivalHeroContent } from "@/lib/festival-landing";
import { getFeaturedPages, getFeaturedPartners, getFeaturedNews } from "@/lib/nav";
import { listProducts } from "@/lib/products";
import FestivalHero from "../../components/festival/FestivalHero";
import ProductReel from "../../components/ProductReel";
import PartnersStrip from "../../components/PartnersStrip";
import NewsTeaser from "../../components/NewsTeaser";

export const dynamic = "force-dynamic";

export default async function FestivalPage() {
  const tNav = await getTranslations("nav");
  const tHome = await getTranslations("home");
  const t = await getTranslations("festivalLanding");

  const [hero, featured, products, partners, featuredNews] = await Promise.all([
    getFestivalHeroContent(),
    getFeaturedPages(),
    listProducts({ publicOnly: true }),
    getFeaturedPartners(),
    getFeaturedNews(6),
  ]);

  return (
    <main className="relative w-full bg-black">
      <FestivalHero
        image={AboutImg}
        badge={hero.badge}
        pages={featured}
        emptyPagesMessage={t("emptyFeatured")}
      />

      <ProductReel products={products} />

      <PartnersStrip partners={partners} heading={tNav("partners")} />

      <NewsTeaser
        posts={featuredNews}
        heading={tNav("news")}
        viewAllLabel={tHome("allNews")}
      />
    </main>
  );
}
