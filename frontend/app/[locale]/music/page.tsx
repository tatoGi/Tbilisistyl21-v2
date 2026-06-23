import { getLocale } from 'next-intl/server';
import { listMusicTracks } from '@/lib/music-tracks';
import { t } from '@/lib/utils';
import type { MusicTrack } from '@/lib/types';

export default async function MusicPage() {
  const locale = await getLocale();
  let tracks: MusicTrack[] = [];
  try {
    tracks = await listMusicTracks(locale);
  } catch {
    // API unavailable
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">
        {locale === 'ka' ? 'მუსიკა' : 'Music'}
      </h1>

      {tracks.length === 0 ? (
        <p className="text-gray-400">
          {locale === 'ka' ? 'ტრეკები არ მოიძებნა' : 'No tracks available'}
        </p>
      ) : (
        <div className="space-y-4">
          {tracks.map((track, index) => (
            <div
              key={track.id}
              className="bg-surface-light border border-border rounded-lg p-4 flex items-center gap-4"
            >
              <span className="text-gray-500 text-lg font-mono w-8">
                {String(index + 1).padStart(2, '0')}
              </span>
              <div className="flex-1">
                <h2 className="font-semibold">{t(track.title, locale)}</h2>
                <p className="text-gray-400 text-sm">{track.artist}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
