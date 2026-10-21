import { expect, test } from '@playwright/test';
import { logIn, postponeTourPrompt, t } from '../support/session.js';

// Writes a real comment into whatever database the server is on: one per run.
test('user comments under a post from the feed', async ({ page }) => {
    await postponeTourPrompt(page);
    await logIn(page);
    // The Following tab, since the seeded user's recommendations may be empty.
    await page.goto('/?feed=following');

    await page.locator('[data-tour="feed.card"]').getByRole('link', { name: await t(page, 'posts.see_full') }).click();
    await expect(page).toHaveURL(/\/groups\/[^/]+\/posts\/[^/]+$/);

    const field = page.getByPlaceholder(await t(page, 'comments.placeholder'));
    const text = `Playwright comment ${Date.now()}`;
    await field.fill(text);

    const posted = page.waitForResponse((r) => r.url().endsWith('/comments') && r.request().method() === 'POST');
    await page.getByRole('button', { name: await t(page, 'comments.submit'), exact: true }).click();

    expect((await posted).ok()).toBe(true);
    await expect(page.getByText(text)).toBeVisible();
    // The form is cleared for the next comment.
    await expect(field).toHaveValue('');
});
