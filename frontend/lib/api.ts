export class ApiError extends Error {
  constructor(
    public status: number,
    public body: unknown,
  ) {
    super(`API error ${status}`);
  }
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function api<T>(
  path: string,
  options?: RequestInit & { locale?: string },
): Promise<T> {
  const { locale, ...fetchOptions } = options || {};

  const res = await fetch(`${API_URL}${path}`, {
    ...fetchOptions,
    headers: {
      Accept: 'application/json',
      'Accept-Language': locale || 'ka',
      ...fetchOptions?.headers,
    },
    next: { revalidate: 60 },
  } as RequestInit);

  if (!res.ok) {
    throw new ApiError(res.status, await res.json().catch(() => null));
  }

  return res.json();
}
