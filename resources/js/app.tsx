import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

// Function to update CSRF token
const updateCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
};

// Configure axios defaults for CSRF
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
updateCsrfToken();

// Update CSRF token on every page load/navigation
axios.interceptors.response.use(
    (response) => {
        // Update CSRF token from response headers if available
        const newToken = response.headers['x-csrf-token'];
        if (newToken) {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                metaTag.setAttribute('content', newToken);
                updateCsrfToken();
            }
        }
        return response;
    },
    (error) => {
        // On 419 error, refresh the page to get a new CSRF token
        if (error.response?.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    },
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Update CSRF token when Inertia page loads
        updateCsrfToken();

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
