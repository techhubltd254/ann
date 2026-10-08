import type { ComponentType, ReactNode } from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import AdminLayout from './layouts/admin-layout';

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob<{ default: ComponentType & { layout?: (page: ReactNode) => ReactNode } }>('/src/**/*.tsx', { eager: true });
    let page = pages[`/src/${name}.tsx`];
    if (!page) page = pages[`/src/app/${name}.tsx`];
    if (!page) throw new Error(`Page not found: ${name}`);
    const component = page.default;
    component.layout = component.layout || ((page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>);
    return component;
  },
  setup({ el, App, props }) {
    if (import.meta.env.SSR) {
      hydrateRoot(el, <App {...props} />);
      return;
    }
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#FFCD05',
    includeCSS: true,
  },
});
