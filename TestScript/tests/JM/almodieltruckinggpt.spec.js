
import { test, expect } from '@playwright/test';

// ======================================
// CONFIGURATION
// ======================================

const BASE_URL = process.env.BASE_URL ||
    'http://localhost/almodieltrucking/';

const CUSTOMER_PHONE =
    process.env.TEST_CUSTOMER_PHONE || '09123132131';

const CUSTOMER_PASSWORD =
    process.env.TEST_CUSTOMER_PASSWORD || '123456';

const FIELD = {
    branch: 'Example: ZEST-O Silay Branch',
    pickupDate: 'Select pickup date and time',
    cargoName: 'e.g. Construction materials',
    quantity: 'Quantity',
    cargoCondition: 'e.g. Fragile, sealed, dry',
    description: 'Describe the cargo',
    instructions: 'Special handling instructions',
    pickupSearch: 'Search pickup location…',
    destinationSearch: 'Search destination location…'
};

// ======================================
// REUSABLE LOCATORS
// ======================================

function textField(page, placeholder) {
    return page.getByPlaceholder(placeholder, {
        exact: true
    });
}

// Pickup date locator
function pickupDateField(page) {
    return page.locator(
        'input[type="datetime-local"], input[placeholder="Select pickup date and time"]'
    ).first();
}

// Quantity locator (single cargo)
function quantityField(page) {
    return page.locator(
        'input[type="number"], input[name="quantity"], input[placeholder="Quantity"]'
    ).first();
}

// Quantity locator (multiple cargo)
function quantityFields(page) {
    return page.locator(
        'input[type="number"], input[name="quantity"], input[placeholder="Quantity"]'
    );
}

// ======================================
// DATE FUNCTION
// ======================================

function dateTimeFromToday(dayOffset, hour = 15, minute = 55) {

    const date = new Date();

    date.setDate(date.getDate() + dayOffset);

    const pad = value =>
        String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(hour)}:${pad(minute)}`;
}

// ======================================
// CUSTOMER LOGIN
// ======================================

async function loginAsCustomer(page) {

    await page.goto(BASE_URL);

    await page.getByRole('link', {
        name: 'Login'
    }).click();

    await page.getByRole('textbox', {
        name: 'Phone Number *'
    }).fill(CUSTOMER_PHONE);

    await page.getByRole('textbox', {
        name: 'Password *'
    }).fill(CUSTOMER_PASSWORD);

    await page.getByRole('button', {
        name: /Sign In/i
    }).click();

    await expect(
        page.getByRole('link', {
            name: /Bookings/
        })
    ).toBeVisible();
}

// ======================================
// OPEN BOOKING FORM
// ======================================

async function openAddBooking(page) {

    await page.getByRole('link', {
        name: /Bookings/
    }).click();

    await page.getByRole('button', {
        name: /Add Booking/
    }).click();

    await expect(
        textField(page, FIELD.branch)
    ).toBeVisible();
}

// ======================================
// FILL BOOKING DETAILS
// ======================================

async function fillBookingDetails(page, options = {}) {

    const branch = options.branch ||
        `Zest-O QA ${Date.now()}`;

    const pickupDate = options.pickupDate ||
        dateTimeFromToday(2);

    await textField(page, FIELD.branch)
        .fill(branch);

    await pickupDateField(page)
        .fill(pickupDate);

    return {
        branch,
        pickupDate
    };
}

// ======================================
// NEXT STEP: CARGO
// ======================================

async function goToCargoStep(page) {

    await page.getByRole('button', {
        name: /Next/
    }).click();

    await expect(
        textField(page, FIELD.cargoName).first()
    ).toBeVisible();
}

// ======================================
// FILL CARGO DETAILS
// ======================================

async function fillCargo(page, options = {}) {

    const cargoName = options.cargoName || 'Dry Goods';

    const quantity = String(options.quantity ?? 10);

    await textField(page, FIELD.cargoName)
        .first()
        .fill(cargoName);

    await quantityField(page).fill(quantity);

    await textField(page, FIELD.cargoCondition)
        .fill(options.condition || 'Good');

    await textField(page, FIELD.description)
        .fill(options.description || 'QA booking cargo');

    await textField(page, FIELD.instructions)
        .fill(options.instructions || 'Handle with care');
}

// ======================================
// NEXT STEP: LOCATION
// ======================================

async function goToLocationStep(page) {

    await page.getByRole('button', {
        name: /Next/
    }).click();

    await expect(
        textField(page, FIELD.pickupSearch)
    ).toBeVisible();

    await expect(
        textField(page, FIELD.destinationSearch)
    ).toBeVisible();
}

// ======================================
// SELECT PICKUP AND DESTINATION
// ======================================

async function selectRecordedLocations(page) {

    const pickup = textField(page, FIELD.pickupSearch);
    const destination = textField(page, FIELD.destinationSearch);

    await pickup.fill('bata');

    const pickupSuggestion = page.getByText(
        'Don Mariano Lacson Highway,',
        { exact: false }
    ).first();

    await expect(pickupSuggestion).toBeVisible();

    await pickupSuggestion.click();

    await destination.fill('talisay');

    const destinationSuggestion = page.getByText(
        'Menlo Village Ⅰ, Zone 10,',
        { exact: false }
    ).first();

    await expect(destinationSuggestion).toBeVisible();

    await destinationSuggestion.click();

    // Verify that the location fields are no longer empty.
    await expect(pickup).not.toHaveValue('');
    await expect(destination).not.toHaveValue('');
}

// ======================================
// REACH SAVE BOOKING
// ======================================


async function reachSaveBooking(page) {

    console.log('\n========== BOOKING NAVIGATION DEBUG ==========');

    const nextButton = page.getByRole('button', {
        name: /Next/i
    });

    await expect(nextButton).toBeVisible({
        timeout: 10000
    });

    await expect(nextButton).toBeEnabled();

    console.log('\nBEFORE CLICKING NEXT:');
    console.log(await page.locator('body').innerText());

    // Click Next
    await nextButton.click();

    // Wait for possible navigation or rendering
    await page.waitForTimeout(2000);

    console.log('\nAFTER CLICKING NEXT:');
    console.log(await page.locator('body').innerText());

    // Check visible buttons
    console.log('\nVISIBLE BUTTONS:');

    const buttons = await page.locator('button:visible').all();

    for (const button of buttons) {
        console.log({
            text: await button.innerText().catch(() => ''),
            disabled: await button.isDisabled().catch(() => false)
        });
    }

    // Check visible validation messages
    console.log('\nVALIDATION MESSAGES:');

    const errors = page.locator(
        '.error, .invalid-feedback, .text-danger, [role="alert"]'
    );

    console.log(await errors.allTextContents());

    // Check location inputs
    console.log('\nLOCATION INPUTS:');

    const inputs = page.locator('input:visible');

    const count = await inputs.count();

    for (let i = 0; i < count; i++) {
        console.log({
            index: i,
            placeholder: await inputs.nth(i).getAttribute('placeholder'),
            value: await inputs.nth(i).inputValue().catch(() => '')
        });
    }

    console.log('\n========== END DEBUG ==========');

    // Check final button
    const saveButton = page.getByRole('button', {
        name: /Save Booking/i
    });

    if (await saveButton.isVisible().catch(() => false)) {
        console.log('Save Booking button successfully found.');
        return;
    }

    console.log('Save Booking button is not visible yet.');
    return;
}


// ======================================
// CONFIRM BOOKING
// ======================================


async function confirmBookingSave(page) {

    console.log('Checking final booking step...');

    const saveButton = page.getByRole('button', {
        name: /Save Booking/i
    });

    // Wait for the final button to appear
    await expect(saveButton).toBeVisible({
        timeout: 10000
    });

    await expect(saveButton).toBeEnabled();

    console.log('Save Booking button found.');

    // Submit booking
    await saveButton.click();

    console.log('Save Booking clicked.');

    // Wait for confirmation
    await expect(
        page.getByText(/booking.*success|successfully.*booking/i)
    ).toBeVisible({
        timeout: 15000
    });
    
    console.log('Booking successfully submitted.');
}

// ======================================
// BOOKING TEST SCENARIOS
// ======================================

test.describe('AlmodielTrucking - Booking Scenarios', () => {

    test.describe.configure({
        timeout: 60000
    });

    test.beforeEach(async ({ page }) => {

        await loginAsCustomer(page);

        await openAddBooking(page);
    });

    // ==================================
    // TEST 1: EMPTY BOOKING FORM
    // ==================================

    test('01 - Reject empty booking form', async ({ page }) => {

        await page.getByRole('button', {
            name: /Next/
        }).click();

        const dialog = page.getByRole('dialog', {
            name: 'Missing Required Fields'
        });

        await expect(dialog).toBeVisible();

        await expect(
            dialog.getByText('Pickup Date & Time')
        ).toBeVisible();

        await dialog.getByRole('button', {
            name: 'OK'
        }).click();

        await expect(dialog).toBeHidden();
    });

    // ==================================
    // TEST 2: MISSING PICKUP DATE
    // ==================================

    test('02 - Require pickup date', async ({ page }) => {

        await textField(page, FIELD.branch)
            .fill('Zest-O QA');

        const dateInput = pickupDateField(page);

        await expect(dateInput).toBeVisible();

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(dateInput).toBeVisible();
    });

    // ==================================
    // TEST 3: PAST PICKUP DATE
    // ==================================

    test('03 - Reject past pickup date', async ({ page }) => {

        await fillBookingDetails(page, {
            pickupDate: dateTimeFromToday(-1)
        });

        const dateInput = pickupDateField(page);

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(dateInput).toBeVisible();
    });

    // ==================================
    // TEST 4: EMPTY CARGO DETAILS
    // ==================================

    test('04 - Require cargo details', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(
            textField(page, FIELD.cargoName).first()
        ).toBeVisible();
    });

    // ==================================
    // TEST 5: ZERO QUANTITY
    // ==================================

    test('05 - Reject zero cargo quantity', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await fillCargo(page, {
            quantity: 0
        });

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(
            quantityField(page)
        ).toBeVisible();
    });

    // ==================================
    // TEST 6: NEGATIVE QUANTITY
    // ==================================

    test('06 - Reject negative cargo quantity', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await fillCargo(page, {
            quantity: -5
        });

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(
            quantityField(page)
        ).toBeVisible();
    });

    // ==================================
    // TEST 7: MULTIPLE CARGO ITEMS
    // ==================================

    test('07 - Allow multiple cargo items', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await page.getByRole('button', {
            name: /Add Cargo/
        }).click();

        const cargoNames = textField(
            page,
            FIELD.cargoName
        );

        const quantities = quantityFields(page);

        await expect(cargoNames).toHaveCount(2);

        await expect(quantities).toHaveCount(2);

        await cargoNames.nth(0).fill('Dry Goods');

        await quantities.nth(0).fill('10');

        await cargoNames.nth(1).fill('Wet Goods');

        await quantities.nth(1).fill('20');

        await textField(page, FIELD.cargoCondition)
            .fill('Good');

        await textField(page, FIELD.description)
            .fill('Two cargo types for QA');

        await textField(page, FIELD.instructions)
            .fill('Keep items separated');

        await goToLocationStep(page);
    });

    // ==================================
    // TEST 8: BACK BUTTON
    // ==================================

    test('08 - Preserve details when going Back', async ({ page }) => {

        const details = await fillBookingDetails(page, {
            branch: 'Zest-O Back Test'
        });

        await goToCargoStep(page);

        await page.getByRole('button', {
            name: /Back/
        }).click();

        await expect(
            textField(page, FIELD.branch)
        ).toHaveValue(details.branch);

        await expect(
            pickupDateField(page)
        ).toHaveValue(details.pickupDate);
    });

    // ==================================
    // TEST 9: RESET BUTTON
    // ==================================

    test('09 - Reset cargo fields', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await fillCargo(page);

        await page.getByRole('button', {
            name: /Reset/
        }).click();

        await expect(
            textField(page, FIELD.cargoName).first()
        ).toHaveValue('');

        await expect(
            quantityField(page)
        ).toHaveValue('');
    });

    // ==================================
    // TEST 10: MISSING LOCATIONS
    // ==================================

    test('10 - Require pickup and destination locations', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await fillCargo(page);

        await goToLocationStep(page);

        await page.getByRole('button', {
            name: /Next/
        }).click();

        await expect(
            textField(page, FIELD.pickupSearch)
        ).toBeVisible();

        await expect(
            textField(page, FIELD.destinationSearch)
        ).toBeVisible();
    });

    // ==================================
    // TEST 11: COMPLETE BOOKING
    // ==================================

    test('11 - Successfully submit complete booking', async ({ page }) => {

        await fillBookingDetails(page);

        await goToCargoStep(page);

        await fillCargo(page, {
            cargoName: `Dry Goods ${Date.now()}`,
            quantity: 10,
            description: 'Complete booking happy-path test'
        });

        await goToLocationStep(page);

        await reachSaveBooking(page);

        await confirmBookingSave(page); 

    });

});
