import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Students — Full Coverage', () => {
  test('add/drop report', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/AddDrop.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('associated parents', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/AddUsers.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('change password', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/ChangePassword.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('enrollment report', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/EnrollmentReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('goal report', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/GoalReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print letters', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/Letters.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('mailing labels', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/MailingLabels.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print student contact info', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/PrintStudentContactInfo.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print student info', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/PrintStudentInfo.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('student labels', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/StudentLabels.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('student re-enroll', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/StudentReenroll.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
