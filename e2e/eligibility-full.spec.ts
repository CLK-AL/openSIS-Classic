import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Eligibility — Full Coverage', () => {
  test('add activity', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/AddActivity.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('enter eligibility', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/EnterEligibility.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('entry times', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/EntryTimes.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('teacher completion', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/TeacherCompletion.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
