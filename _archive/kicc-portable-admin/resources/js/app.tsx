import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AdminLayout from './layouts/admin-layout';

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('/src/**/*.tsx', { eager: true });
    const key = `/src/${name}.tsx`;
    if (!pages[key]) throw new Error(`Page not found: ${name}`);
    const component = pages[key].default;
    component.layout = component.layout || ((page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>);
    return component;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: { color: '#FFCD05', includeCSS: true },
});
