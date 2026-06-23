import { api } from './api';
import type { Post } from './types';

export async function listPosts(locale: string) {
  const res = await api<{ data: Post[] }>('/api/posts', { locale });
  return res.data;
}
