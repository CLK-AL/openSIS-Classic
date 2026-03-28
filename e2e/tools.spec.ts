import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Tools Module', () => {

  test('access log page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/LogDetails.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Log|Login|Record/i);
  });

  test('backup page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Backup.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Backup|Database/i);
  });

  test('data import page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/DataImport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Import|Data|Student|Staff/i);
  });

  test('API token page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/GenerateApi.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Token|Key|Secret/i);
  });

  test('translation manager page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/TranslationManager.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Translation|Language|Import/i);
  });

  test('rollover page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Rollover.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Rollover|School Year/i);
  });

  test('at a glance report loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Reports.php?func=Basic');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|School|Student/i);
  });
});
