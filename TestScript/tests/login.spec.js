// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Login smoke tests for Almodiel Trucking Services.
 *
 * Recorded from the four login screens:
 *   /admin-login      -> admin      -> "sample"
 *   /driver-login     -> driver     -> "trips"
 *   /assistant-login  -> assistant  -> "assistantDashboard"
 *   /customer-login   -> customer   -> "sample"
 *
 * Credentials come from the seeded `employee` / `customer` rows.
 * Override with environment variables when running against another dataset:
 *   ADMIN_PHONE / ADMIN_PASSWORD, etc.
 */
const USERS = {
  admin: {
    path: 'admin-login',
    heading: 'Admin Login',
    phone: process.env.ADMIN_PHONE || '09123456789',
    password: process.env.ADMIN_PASSWORD || '123456',
    landsOn: /\/sample$/,
  },
  driver: {
    path: 'driver-login',
    heading: 'Driver Login',
    phone: process.env.DRIVER_PHONE || '09320312321',
    password: process.env.DRIVER_PASSWORD || '123456',
    landsOn: /\/trips$/,
  },
  assistant: {
    path: 'assistant-login',
    heading: 'Assistant Login',
    phone: process.env.ASSISTANT_PHONE || '09321312321',
    password: process.env.ASSISTANT_PASSWORD || '123456',
    landsOn: /\/assistantDashboard$/,
  },
  customer: {
    path: 'customer-login',
    heading: 'Welcome!',
    phone: process.env.CUSTOMER_PHONE || '09123132131',
    password: process.env.CUSTOMER_PASSWORD || '123456',
    landsOn: /\/sample$/,
  },
};

/** Fill and submit the shared login form, then wait for the app's JS redirect. */
async function logIn(page, user) {
  await page.goto(user.path);
  await expect(page.getByRole('heading', { name: user.heading })).toBeVisible();

  await page.locator('#phoneNumber').fill(user.phone);
  await page.locator('#password').fill(user.password);
  await page.getByRole('button', { name: 'Sign In' }).click();

  await page.waitForURL(user.landsOn, { timeout: 15_000 });
}

for (const [role, user] of Object.entries(USERS)) {
  test(`${role} can log in`, async ({ page }) => {
    await logIn(page, user);

    // The authenticated shell (sidebar + header) is only rendered after login.
    await expect(page.locator('.layout-wrapper')).toBeVisible();
    await expect(page.getByText(/User or password incorrect/)).toHaveCount(0);
  });
}

test('wrong password is rejected', async ({ page }) => {
  await page.goto(USERS.admin.path);
  await page.locator('#phoneNumber').fill(USERS.admin.phone);
  await page.locator('#password').fill('definitely-not-the-password');
  await page.getByRole('button', { name: 'Sign In' }).click();

  await expect(page.getByText(/User or password incorrect/)).toBeVisible();
  await expect(page).toHaveURL(/\/admin-login$/);
});
