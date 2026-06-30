"use client";

import { usePathname } from "next/navigation";
import type { SocialLinks, SiteContact } from "@/lib/nav";
import type { MusicTrack } from "@/lib/music-tracks";
import FestivalMenu, { type NavLink } from "./FestivalMenu";
import Footer from "./Footer";
import TicketCta from "./TicketCta";
import FestivalMusic from "./festival/FestivalMusic";
import MusicTrackList from "./festival/MusicTrackList";

/** Background-music track shared site-wide (mirrors lib/festival-landing). */
type SiteMusic = { url: string; title: string | null; loop: boolean };

// Routes that render their own full-screen / standalone UI and must NOT get the
// shared header, footer and burger menu.
function isBareRoute(pathname: string | null): boolean {
  if (!pathname) return false;
  return (
    pathname === "/" || // landing splash ("ENTER THE ENERGY")
    pathname === "/admin-login" ||
    pathname.startsWith("/_legacy-admin")
  );
}

/**
 * Single source of the site chrome (menu + footer + floating ticket CTA) for
 * the whole public site, so every page — dashboard routes AND CMS `[slug]`
 * pages like /main-stage — shares one consistent layout.
 */
export default function SiteChrome({
  pages,
  social,
  contact,
  music,
  musicTracks,
  children,
}: {
  pages: NavLink[];
  social: SocialLinks;
  contact: SiteContact;
  music: SiteMusic | null;
  musicTracks: MusicTrack[];
  children: React.ReactNode;
}) {
  const pathname = usePathname();

  if (isBareRoute(pathname)) {
    return <>{children}</>;
  }

  return (
    <div className="relative flex min-h-screen flex-col bg-black text-white">
      <FestivalMenu pages={pages} social={social} />
      <div className="flex-1">{children}</div>
      <Footer social={social} contact={contact} />
      <TicketCta />
      {/* Site-wide background music: the mini track-list player lives in the
          persistent layout so playback continues across page navigation. */}
      <MusicTrackList tracks={musicTracks} />
      {music ? (
        <FestivalMusic src={music.url} title={music.title} loop={music.loop} />
      ) : null}
    </div>
  );
}
