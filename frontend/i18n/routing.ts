import { defineRouting } from 'next-intl/routing';

export const routing = defineRouting({
  locales: ['ka', 'en', 'ru', 'ua'],
  defaultLocale: 'ka',
});
