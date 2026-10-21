import { expect, test } from '@playwright/test';
import { logIn, postponeTourPrompt, t } from '../support/session.js';

// Waits until driver.js has finished moving the highlight to the step, i.e. the overlay's cut-out
// stays put for a few frames. The popover's text changes halfway through that animation, but
// driver.js only takes the step as reached once it ends: if no step ever gets that far, closing
// the tour doesn't call onDestroyed, and the tour isn't saved as taken.
async function waitForStepToSettle(page) {
    await page.waitForFunction(
        () => {
            const d = document.querySelector('.driver-overlay path')?.getAttribute('d');
            window.__tourStill = d && d === window.__tourLastD ? (window.__tourStill ?? 0) + 1 : 0;
            window.__tourLastD = d;

            return window.__tourStill >= 3;
        },
        null,
        { polling: 'raf' },
    );
}

// Taken from the account menu rather than the "New here?" prompt: the prompt is gone for good
// once the user has answered it, while the menu item always runs the tour.
test('user takes the home page tour from the account menu', async ({ page }) => {
    await postponeTourPrompt(page);
    await logIn(page);
    // The Following tab, since the seeded user's recommendations may be empty, and the feed
    // tour has a step on the first post card.
    await page.goto('/?feed=following');

    await page.getByRole('button', { name: await t(page, 'nav.account_menu') }).click();
    await page.getByRole('button', { name: await t(page, 'tours.menu_item') }).click();

    const popover = page.locator('.driver-popover');
    const progress = popover.locator('.driver-popover-progress-text');

    // The home page runs the navbar tour first, so it starts on the logo.
    await expect(progress).toHaveText(/^1\D+\d+$/);
    await expect(page.locator('[data-tour="navigation.home"]')).toHaveClass(/driver-active-element/);

    // "1 of 7", or however the user's language puts it: the last number is the total.
    const total = Number((await progress.textContent()).match(/(\d+)$/)[1]);
    const next = await t(page, 'tours.buttons.next');

    for (let step = 2; step <= total; step++) {
        await waitForStepToSettle(page);
        await popover.getByRole('button', { name: next }).click();
        await expect(progress).toHaveText(new RegExp(`^${step}\\D+${total}$`));
    }

    // Both tours (navigation and feed) are saved as completed when the tour ends.
    const saved = Promise.all(
        ['navigation', 'feed'].map((tour) =>
            page.waitForResponse((r) => r.url().endsWith('/settings/tours') && r.request().postData()?.includes(`"${tour}"`)),
        ),
    );
    await waitForStepToSettle(page);
    await popover.getByRole('button', { name: await t(page, 'tours.buttons.done') }).click();

    await expect(popover).toBeHidden();
    for (const response of await saved) {
        expect(response.status()).toBe(204);
    }
});
