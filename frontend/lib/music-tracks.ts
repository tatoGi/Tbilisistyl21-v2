import { api } from "./api";
import { t, mediaUrl } from "./utils";
import { getCurrentLocale } from "./locale";
import type { MusicTrack as ApiMusicTrack, Media } from "./types";

export type MusicTrack = {
  id: string;
  title: string;
  artist: string;
  audioUrl: string;
  order: number;
  status: "active" | "draft";
};

type ApiMusicTrackWithFile = ApiMusicTrack & { audio_file?: Media | null };

function audioUrlOf(track: ApiMusicTrackWithFile): string {
  const file = track.audio_file;
  if (file && file.filename) return mediaUrl(file.filename);
  return "";
}

export async function listMusicTracks({
  publicOnly = false,
}: { publicOnly?: boolean } = {}): Promise<MusicTrack[]> {
  let locale: string;
  try {
    locale = await getCurrentLocale();
  } catch {
    locale = "ka";
  }

  // Runs in the root layout on every page. Degrade to an empty list if the API
  // is unavailable rather than throwing and 500-ing every route.
  let docs: ApiMusicTrackWithFile[];
  try {
    const res = await api<{ data: ApiMusicTrackWithFile[] }>("/api/music-tracks", {
      locale,
    });
    docs = Array.isArray(res?.data) ? res.data : [];
  } catch {
    return [];
  }

  return docs
    .map((d) => {
      const audioUrl = audioUrlOf(d);
      if (!audioUrl) return null;
      const track: MusicTrack = {
        id: String(d.id),
        title: t(d.title, locale),
        artist: d.artist ?? "",
        audioUrl,
        order: Number(d.order ?? 0),
        status: (d.status as "active" | "draft") ?? "draft",
      };
      if (publicOnly && track.status !== "active") return null;
      return track;
    })
    .filter((t0): t0 is MusicTrack => t0 !== null);
}
