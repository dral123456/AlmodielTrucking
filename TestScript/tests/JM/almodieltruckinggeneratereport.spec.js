import { test, expect } from '@playwright/test';

test('test', async ({ page }) => {
  await page.goto('http://localhost/almodieltrucking/admin-login');
  await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123456789');
  await page.getByRole('textbox', { name: 'Password *' }).click();
  await page.getByRole('textbox', { name: 'Password *' }).fill('123456');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.getByRole('link', { name: ' Reports' }).click();
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: ' CSV' }).click();
  const download = await downloadPromise;
});