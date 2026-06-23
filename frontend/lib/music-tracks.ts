import { api } from './api';
import type { MusicTrack } from './types';

export async function listMusicTracks(locale: string) {
  const res = await api<{ data: MusicTrack[] }>('/api/music-tracks', { locale });
  return res.data;
}
