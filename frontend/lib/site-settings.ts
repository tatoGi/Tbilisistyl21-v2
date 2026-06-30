import { api } from "./api";
import type { Translatable } from "./types";

export type SiteSettingsData = {
  hero?: {
    badge?: Translatable;
    heading?: Translatable;
    subheading?: Translatable;
    image?: string | null;
  };
  instagramUrl?: string | null;
  tiktokUrl?: string | null;
  contact?: {
    phone?: string | null;
    phoneHref?: string | null;
    email?: string | null;
    address?: string | null;
  };
  [key: string]: unknown;
};

/** All site settings managed in the admin (Site settings page). */
export async function getSiteSettings(): Promise<SiteSettingsData> {
  try {
    const res = await api<{ data: SiteSettingsData }>("/api/site-settings", { fresh: true });
    return res?.data && typeof res.data === "object" ? res.data : {};
  } catch {
    return {};
  }
}
