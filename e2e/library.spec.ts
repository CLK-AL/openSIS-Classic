import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Library Module', () => {

  test('book catalog page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Books.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Book|Catalog|Title|Author/i);
  });

  test('book catalog has add form', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Books.php');
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="author"]')).toBeVisible();
    await expect(page.locator('input[name="isbn"]')).toBeVisible();
  });

  test('checkout page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Checkout.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Checkout|Book|Due|Borrower/i);
  });

  test('checkout form has required fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Checkout.php');
    await expect(page.locator('select[name="book_id"]')).toBeVisible();
    await expect(page.locator('select[name="borrower_type"]')).toBeVisible();
    await expect(page.locator('input[name="due_date"]')).toBeVisible();
  });

  test('overdue books page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Overdue.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Overdue|Book/i);
  });

  test('book report page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/BookReport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|Book|Total|Available/i);
  });

  test('categories page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Categories.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Categor/i);
  });

  test('ical export link exists on checkout page', async ({ adminPage: page }) => {
    await navigateTo(page, 'library/Checkout.php');
    await expect(page.locator('a[href*="modfunc=ical"]')).toBeVisible();
  });
});
