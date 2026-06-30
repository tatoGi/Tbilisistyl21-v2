import { getFestivalHeroContent } from "@/lib/festival-landing";
import { getFeaturedPages, getFeaturedPartners, getFeaturedNews } from "@/lib/nav";
import { listProducts } from "@/lib/products";
import FestivalHero from "../../components/festival/FestivalHero";
import ProductReel from "../../components/ProductReel";
import PartnersStrip from "../../components/PartnersStrip";
import NewsTeaser from "../../components/NewsTeaser";

export const dynamic = "force-dynamic";

export default async function FestivalPage() {
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
        imageUrl={hero.imageUrl}
        pages={featured}
      />

      <ProductReel products={products} />

      {partners.length > 0 ? <PartnersStrip partners={partners} heading="" /> : null}

      {featuredNews.length > 0 ? (
        <NewsTeaser posts={featuredNews} heading="" viewAllLabel="" />
      ) : null}
    </main>
  );
}
