export interface Translatable {
  ka?: string;
  en?: string;
  ru?: string;
  ua?: string;
}

export interface Ticket {
  id: string;
  title: Translatable;
  description: Translatable;
  image?: string | null;
  price_gel: number;
  quantity: number;
  event_date: string;
  location: string;
  status: string;
  sale_url: string | null;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: string;
  title: Translatable;
  description: Translatable;
  price_gel: number;
  category: string;
  is_vip: boolean;
  image_id: string | null;
  image: Media | null;
  status: string;
  sizes: ProductSize[];
  created_at: string;
  updated_at: string;
}

export interface ProductSize {
  id: string;
  product_id: string;
  size: string;
  quantity: number;
}

export interface Media {
  id: string;
  filename: string;
  path: string;
  mime_type: string;
  size: number;
  alt: string | null;
}

export interface MusicTrack {
  id: string;
  title: Translatable;
  artist: string;
  audio_file_id: string | null;
  order: number;
  status: string;
}

export interface Page {
  id: string;
  title: Translatable;
  nav_label: Translatable;
  slug: string;
  route_path: string | null;
  show_in_nav: boolean;
  nav_order: number;
  featured_on_home: boolean;
  layout: string;
  content_blocks: unknown[];
  is_published: boolean;
}

export interface Post {
  id: string;
  title: Translatable;
  slug: string;
  status: string;
  featured: boolean;
  created_at: string;
  cover?: string | null;
  excerpt?: Translatable;
  content_blocks?: unknown[];
}

export interface Partner {
  id: string;
  name: string;
  description: Translatable;
  logo_id: string | null;
  logo: Media | null;
  url: string;
  order: number;
}

export interface SiteSettings {
  [key: string]: unknown;
}
