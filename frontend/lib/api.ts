export class ApiError extends Error {
  constructor(
    public status: number,
    public body: unknown,
  ) {
    super(`API error ${status}`);
  }
}

/** Server-side SSR uses the Docker-internal Laravel URL when available. */
function apiBaseUrl(): string {
  if (typeof window === 'undefined' && process.env.API_INTERNAL_URL) {
    return process.env.API_INTERNAL_URL;
  }
  return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
}

export async function api<T>(
  path: string,
  options?: RequestInit & { locale?: string; /** Skip Next.js cache — use for CMS content. */ fresh?: boolean },
): Promise<T> {
  const { locale, fresh, ...fetchOptions } = options || {};

  const res = await fetch(`${apiBaseUrl()}${path}`, {
    ...fetchOptions,
    headers: {
      Accept: 'application/json',
      'Accept-Language': locale || 'ka',
      ...fetchOptions?.headers,
    },
    ...(fresh ? { cache: 'no-store' as RequestCache } : { next: { revalidate: 60 } }),
  } as RequestInit);

  if (!res.ok) {
    throw new ApiError(res.status, await res.json().catch(() => null));
  }

  return res.json();
}
