import { test, expect } from '@playwright/test';
const { chromium } = require('playwright');

const logintest = ('logintest', async () => {
  const browser = await chromium.launch({
    headless: false
  });
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto('http://localhost/almodieltrucking/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ArrowRight');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ArrowRight');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ArrowRight');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ArrowRight');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ArrowRight');
  await page.getByRole('textbox', { name: 'Phone Number *' }).dblclick();
  await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123123123');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('Tab');
  await page.getByRole('textbox', { name: 'Password *' }).fill('123456');
  await page.getByRole('textbox', { name: 'Password *' }).press('Enter');
  await page.getByRole('textbox', { name: 'Phone Number *' }).dblclick();
  await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123132131');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('Enter');
  await page.close();

  // ---------------------
  await context.close();
  await browser.close();
});

const booking_right = (async () => {
  const browser = await chromium.launch({
    headless: false
  });
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto('http://localhost/almodieltrucking/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('ControlOrMeta+a');
  await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123132131');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('Enter');
  await page.getByRole('link', { name: ' Bookings' }).click();
  await page.getByRole('button', { name: ' Add Booking' }).click();
  await page.getByRole('textbox', { name: 'Example: ZEST-O Silay Branch' }).click();
  await page.getByRole('textbox', { name: 'Example: ZEST-O Silay Branch' }).fill('Zest-O');
  await page.getByRole('textbox', { name: 'Example: ZEST-O Silay Branch' }).press('Tab');
  await page.getByRole('textbox', { name: 'Select pickup date and time' }).click();
  await page.getByRole('textbox', { name: 'Select pickup date and time' }).press('ControlOrMeta+a');
  await page.getByRole('textbox', { name: 'Select pickup date and time' }).press('ControlOrMeta+x');
  await page.getByRole('textbox', { name: 'Select pickup date and time' }).fill('2026-11-25 15:55');
  await page.getByRole('button', { name: 'Next ' }).click();
  await page.getByRole('textbox', { name: 'e.g. Construction materials' }).click();
  await page.getByRole('textbox', { name: 'e.g. Construction materials' }).fill('Dry');
  await page.getByRole('textbox', { name: 'e.g. Construction materials' }).press('Tab');
  await page.getByRole('spinbutton', { name: 'Quantity' }).fill('10');
  await page.getByRole('spinbutton', { name: 'Quantity' }).press('Tab');
  await page.getByRole('textbox', { name: 'e.g. Fragile, sealed, dry' }).fill('Good');
  await page.getByRole('textbox', { name: 'e.g. Fragile, sealed, dry' }).press('Tab');
  await page.getByRole('textbox', { name: 'Describe the cargo' }).fill('Bad');
  await page.getByRole('textbox', { name: 'Describe the cargo' }).press('Tab');
  await page.getByRole('textbox', { name: 'Special handling instructions' }).fill('None');
  await page.getByRole('textbox', { name: 'Special handling instructions' }).press('Tab');
  await page.getByRole('button', { name: ' Reset' }).press('Tab');
  await page.getByRole('button', { name: ' Back' }).press('Tab');
  await page.getByRole('button', { name: 'Next ' }).press('Enter');
  await page.locator('#bookingMap').click();
  await page.locator('#bookingMap').click();
  await page.getByRole('textbox', { name: 'Search pickup location…' }).click();
  await page.getByRole('textbox', { name: 'Search pickup location…' }).fill('bata');
  await page.getByText('Don Mariano Lacson Highway,').first().click();
  await page.getByRole('textbox', { name: 'Search destination location…' }).click();
  await page.getByRole('textbox', { name: 'Search destination location…' }).fill('talisay');
  await page.getByText('Menlo Village Ⅰ, Zone 10,').first().click();
  await page.getByRole('textbox', { name: 'Search pickup location…' }).click();
  await page.getByRole('textbox', { name: 'Search pickup location…' }).fill('baco');
  await page.getByText('Bacolod Airport Access Road,').first().click();
  await page.getByRole('button', { name: 'Next ' }).click();
  await page.getByRole('button', { name: ' Save Booking' }).click();
  await page.getByRole('button', { name: ' Yes, Save' }).click();
  await page.getByRole('button', { name: 'OK' }).click();
  await page.getByRole('button', { name: 'Jethro T. Almodiel almodiel@' }).click();
  await page.getByRole('link', { name: ' Sign Out' }).click();
  await page.getByRole('link', { name: 'Login' }).click();
  await page.goto('http://localhost/almodieltrucking/admin-login');
  await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123456789');
  await page.getByRole('textbox', { name: 'Phone Number *' }).press('Tab');
  await page.getByRole('textbox', { name: 'Password *' }).fill('123456');
  await page.getByRole('textbox', { name: 'Password *' }).press('Enter');
  await page.getByRole('button', { name: 'Notifications' }).click();
  await page.getByRole('link', { name: 'Upcoming Trip #246' }).click();
  await page.getByText('Trip #245', { exact: true }).click();
  await page.close();

  // ---------------------
  await context.close();
  await browser.close();
});

test('almodieltest', async () => {
  await booking_right();
});
// test('almodieltest',async () => {
//   const browser = await chromium.launch({
//     headless: false
//   });
//   const context = await browser.newContext();
//   const page = await context.newPage();
//   await page.goto('http://localhost/almodieltrucking/');
//   await page.getByRole('link', { name: 'Login' }).click();
//   await page.getByRole('textbox', { name: 'Phone Number *' }).click();
//   await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123456789');
//   await page.getByRole('button', { name: 'Sign In' }).click();
//   await page.getByRole('textbox', { name: 'Phone Number *' }).click();
//   await page.getByRole('textbox', { name: 'Phone Number *' }).fill('09123132131');
//   await page.getByRole('textbox', { name: 'Password *' }).click();
//   await page.getByRole('textbox', { name: 'Password *' }).fill('');
//   await page.getByRole('button', { name: 'Sign In' }).click();
//   await page.getByRole('textbox', { name: 'Password *' }).click();
//   await page.getByRole('textbox', { name: 'Password *' }).fill('123456');
//   await page.getByRole('button', { name: 'Sign In' }).click();
//   await page.getByRole('button', { name: 'Jethro T. Almodiel almodiel@' }).click();
//   await page.getByRole('link', { name: ' Bookings' }).click();
//   await page.getByRole('button', { name: ' Add Booking' }).click();
//   await page.getByRole('textbox', { name: 'Example: ZEST-O Silay Branch' }).click();
//   await page.getByRole('textbox', { name: 'Example: ZEST-O Silay Branch' }).fill('Jethro Store');
//   await page.getByRole('textbox', { name: 'Select pickup date and time' }).click();
//   await page.getByRole('textbox', { name: 'Select pickup date and time' }).click();
//   await page.getByRole('button', { name: 'Next ' }).click();
//   await page.getByRole('textbox', { name: 'e.g. Construction materials' }).click();
//   await page.getByRole('textbox', { name: 'e.g. Construction materials' }).fill('solid');
//   await page.getByRole('textbox', { name: 'e.g. Fragile, sealed, dry' }).click();
//   await page.getByRole('textbox', { name: 'e.g. Fragile, sealed, dry' }).fill('dry');
//   await page.getByRole('spinbutton', { name: 'Quantity' }).click();
//   await page.getByRole('spinbutton', { name: 'Quantity' }).fill('50');
//   await page.getByRole('button', { name: 'Next ' }).click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.getByRole('button', { name: 'Next ' }).click();
//   await page.getByRole('button', { name: 'OK' }).click();
//   await page.locator('#destinationStreet').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.locator('#bookingMap').click();
//   await page.getByRole('button', { name: 'Next ' }).click();
//   await page.getByRole('button', { name: ' Save Booking' }).click();
//   await page.getByRole('button', { name: ' Yes, Save' }).click();
//   await page.getByRole('button', { name: 'OK' }).click();
//   await page.close();

//   // ---------------------
//   await context.close();
//   await browser.close();
// });