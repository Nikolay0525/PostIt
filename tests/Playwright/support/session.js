import { readFileSync } from 'node:fs';
import { expect } from '@playwright/test';

// A signed-in user sees the site in the language saved in their settings, not the browser's,
// so texts are looked up in the same translations the frontend uses (built by laravel-vue-i18n).
// The language is the document's: call it after a full page load (page.goto), since an Inertia
// visit such as the redirect after logging in doesn't update <html lang>.
const translations = {};

export async function t(page, key) {
    const locale = await page.locator('html').getAttribute('lang');
    translations[locale] ??= JSON.parse(readFileSync(new URL(`../../../lang/php_${locale}.json`, import.meta.url), 'utf8'));

    return translations[locale][key] ?? key;
}

// UserSeeder's first user; every factory user has the password "password".
export const USER = {
    email: process.env.E2E_USER_EMAIL ?? 'user1@example.com',
    password: process.env.E2E_USER_PASSWORD ?? 'password',
};

// The account menu is only rendered for a signed-in user.
export const ACCOUNT_MENU = '[data-tour="navigation.account"]';

// exact: the show password button's "Show password" label would match "Password" too.
export async function fillLoginForm(page, email, password) {
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
}

export async function logIn(page, user = USER) {
    await page.goto('/login');
    await fillLoginForm(page, user.email, user.password);
    await expect(page.locator(ACCOUNT_MENU)).toBeVisible();
}

// Answers "Later" to the "New here?" prompt for the whole tab, as TourPrompt does, so it can't
// cover buttons a test clicks. Nothing is saved on the server, unlike "No, thanks".
export async function postponeTourPrompt(page) {
    await page.addInitScript(() => {
        sessionStorage.setItem('tours-later', JSON.stringify(['navigation', 'feed', 'group', 'post', 'own_profile', 'other_profile']));
    });
}
