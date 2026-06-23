import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'TbilisiStyle21',
  description: 'TbilisiStyle21 - Events, Music & Merch',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return children;
}
