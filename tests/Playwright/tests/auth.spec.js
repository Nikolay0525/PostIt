import { expect, test } from '@playwright/test';
import { ACCOUNT_MENU, USER, fillLoginForm, logIn } from '../support/session.js';

test('guest sees the home page with log in and sign up links', async ({ page }) => {
    await page.goto('/');

    // The guest feed has its own log in prompt too, so look in the navbar.
    const nav = page.getByRole('navigation');
    await expect(nav.getByRole('link', { name: 'Log in' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Sign up' })).toBeVisible();
    await expect(page.locator(ACCOUNT_MENU)).toHaveCount(0);
});

test('user logs in and lands on the home page', async ({ page }) => {
    await logIn(page);

    await expect(page).toHaveURL('/');
});

test('wrong password keeps the user on the login page with an error', async ({ page }) => {
    await page.goto('/login');
    await fillLoginForm(page, USER.email, 'definitely-not-the-password');

    await expect(page.locator('.field-error')).toBeVisible();
    await expect(page).toHaveURL('/login');
    await expect(page.locator(ACCOUNT_MENU)).toHaveCount(0);
});
