import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Users — Full Coverage', () => {
  test('user advanced report', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/UserAdvancedReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('staff advanced report', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/UserAdvancedReportStaff.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('user/parent fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/UserFields.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('institute reports', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Reports.php?func=Ins_r');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('institute custom field reports', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Reports.php?func=Ins_cf');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
