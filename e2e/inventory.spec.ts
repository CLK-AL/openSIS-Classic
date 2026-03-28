import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Inventory Module — Equipment', () => {

  test('equipment catalog page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Equipment.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Equipment|Catalog|Name|Serial/i);
  });

  test('equipment form has required fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Equipment.php');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('select[name="condition_status"]')).toBeVisible();
  });

  test('equipment checkout page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Checkout.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Checkout|Equipment|Borrower/i);
  });

  test('maintenance log page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Maintenance.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Maintenance|Log|Type|Date/i);
  });

  test('categories page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Categories.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Categor/i);
  });

  test('locations page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Locations.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Location|Room|Building/i);
  });

  test('inventory report page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/EquipmentReport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|Item|Value|Category/i);
  });
});

test.describe('Inventory Module — Field Trips', () => {

  test('field trips page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/FieldTrips.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Field Trip|Destination|Date/i);
  });

  test('field trip form has required fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/FieldTrips.php');
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="destination"]')).toBeVisible();
    await expect(page.locator('input[name="trip_date"]')).toBeVisible();
  });

  test('field trips ical export link exists', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/FieldTrips.php');
    await expect(page.locator('a[href*="modfunc=ical"]')).toBeVisible();
  });
});

test.describe('Inventory Module — Birthdays', () => {

  test('birthdays page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Birthdays.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Birthday|Student|Month/i);
  });

  test('month navigation buttons visible', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Birthdays.php');
    // 12 month buttons should be present
    const monthBtns = page.locator('a.btn').filter({ hasText: /^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)$/ });
    await expect(monthBtns).toHaveCount(12);
  });

  test('birthdays ical export link exists', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Birthdays.php');
    await expect(page.locator('a[href*="modfunc=ical"]')).toBeVisible();
  });
});

test.describe('Inventory Module — Finance', () => {

  test('collections page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Finance.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Collection|Payment|Category|Amount/i);
  });

  test('finance form has required fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Finance.php');
    await expect(page.locator('select[name="category"]')).toBeVisible();
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="amount"]')).toBeVisible();
  });

  test('finance report page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/FinanceReport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|Collected|Pending|Overdue/i);
  });
});

test.describe('Inventory Module — Donations', () => {

  test('donations page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Donations.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Donation|Donor|Type|Value/i);
  });

  test('donation form has all fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Donations.php');
    await expect(page.locator('select[name="donation_type"]')).toBeVisible();
    await expect(page.locator('input[name="donor_name"]')).toBeVisible();
    await expect(page.locator('select[name="donor_type"]')).toBeVisible();
    await expect(page.locator('input[name="monetary_value"]')).toBeVisible();
    await expect(page.locator('select[name="beneficiary_tag"]')).toBeVisible();
  });

  test('donation type options include all types', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Donations.php');
    const options = page.locator('select[name="donation_type"] option');
    await expect(options).toHaveCount(7); // money, book, equipment, supplies, clothing, food, other
  });

  test('donation stats cards visible', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/Donations.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Total Donation|Cash|Books Donated|Equipment/i);
  });
});

test.describe('Inventory Module — Student Needs', () => {

  test('student needs page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/NeedyStudents.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Student Need|Priority|Funding/i);
  });

  test('need form has required fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/NeedyStudents.php');
    await expect(page.locator('input[name="student_id"]')).toBeVisible();
    await expect(page.locator('select[name="need_type"]')).toBeVisible();
    await expect(page.locator('select[name="priority"]')).toBeVisible();
    await expect(page.locator('input[name="estimated_cost"]')).toBeVisible();
  });

  test('priority options include all levels', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/NeedyStudents.php');
    const options = page.locator('select[name="priority"] option');
    await expect(options).toHaveCount(4); // critical, high, medium, low
  });

  test('stats dashboard shows key metrics', async ({ adminPage: page }) => {
    await navigateTo(page, 'inventory/NeedyStudents.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Critical|Open|Fulfilled|Funding/i);
  });
});
