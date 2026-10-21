import { defineConfig, devices } from '@playwright/test';

// Black-box tests against an already running app (`composer dev`, or `php artisan serve` after
// `npm run build`). They use whatever database that server is on, so point it at a seeded one.
export default defineConfig({
    testDir: './tests',
    outputDir: './test-results',
    fullyParallel: true,
    retries: process.env.CI ? 2 : 0,
    reporter: [['list'], ['html', { outputFolder: './report', open: 'never' }]],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8000',
        // SetLocale picks the interface language from the browser, so the texts in assertions are English.
        locale: 'en-US',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
