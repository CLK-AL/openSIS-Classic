import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Eligibility Module', () => {

  test('student screen loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/Student.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Student|Eligibility/i);
  });

  test('activities setup loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/Activities.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Activit/i);
  });

  test('student list loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'eligibility/StudentList.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
