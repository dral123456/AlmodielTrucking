import { test, expect } from '@playwright/test';

const DRIVER = {
    phone: process.env.DRIVER_PHONE || '09320312321',
    password: process.env.DRIVER_PASSWORD || '123456',
};

test('Driver - Submit Vehicle Breakdown Incident', async ({ page }) => {
    // LOGIN (driver account — the incident form is only rendered for role "driver")
    await page.goto('driver-login');

    await page.locator('#phoneNumber').fill(DRIVER.phone);
    await page.locator('#password').fill(DRIVER.password);

    await page.getByRole('button', { name: 'Sign In' }).click();
    await page.waitForURL(/trips/);

    // INCIDENT REPORT TAB
    await page.getByRole('tab', { name: /Incident Report/ }).click();
    await expect(page.locator('#driverIncidentPane')).toBeVisible();

    // SELECT TRIP — only trips assigned to this driver are listed, so take the first one
    const tripSelect = page.locator('#incidentTripID');
    const tripIDs = await tripSelect
        .locator('option')
        .evaluateAll((options) => options.map((option) => option.value).filter(Boolean));

    expect(tripIDs.length, 'driver has at least one assigned trip').toBeGreaterThan(0);
    await tripSelect.selectOption(tripIDs[0]);

    // INCIDENT DETAILS
    await page.locator('#incidentType').selectOption('vehicle_breakdown');
    await page.locator('#incidentSeverity').selectOption('low');
    await page.locator('#incidentDescription').fill('no break');

    // SUBMIT
    await page.getByRole('button', { name: 'Submit Report' }).click();

    // CONFIRM
    const dialog = page.locator('.swal2-popup');
    await expect(dialog).toContainText('Incident Submitted');

    await dialog.getByRole('button', { name: 'OK' }).click();
    await expect(dialog).toBeHidden();
});
