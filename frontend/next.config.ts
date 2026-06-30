import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./i18n/request.ts');

function storageImagePatterns(): NonNullable<NextConfig['images']>['remotePatterns'] {
  const patterns: NonNullable<NextConfig['images']>['remotePatterns'] = [
    {
      protocol: 'http',
      hostname: 'localhost',
      port: '8000',
      pathname: '/storage/**',
    },
  ];

  const apiUrl = process.env.NEXT_PUBLIC_API_URL;
  if (apiUrl) {
    try {
      const parsed = new URL(apiUrl);
      const protocol = parsed.protocol.replace(':', '') as 'http' | 'https';
      patterns.push({
        protocol,
        hostname: parsed.hostname,
        ...(parsed.port ? { port: parsed.port } : {}),
        pathname: '/storage/**',
      });
    } catch {
      // ignore invalid build-time URL
    }
  }

  return patterns;
}

const nextConfig: NextConfig = {
  output: 'standalone',
  images: {
    remotePatterns: storageImagePatterns(),
  },
  async rewrites() {
    const internalApi =
      process.env.API_INTERNAL_URL ||
      process.env.NEXT_PUBLIC_API_URL ||
      'http://localhost:8000';

    return [
      {
        source: '/api/:path*',
        destination: `${internalApi}/api/:path*`,
      },
      {
        source: '/storage/:path*',
        destination: `${internalApi}/storage/:path*`,
      },
    ];
  },
};

export default withNextIntl(nextConfig);
