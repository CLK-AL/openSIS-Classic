import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Attendance Module', () => {

  test('administration page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/Administration.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Attendance/i);
  });

  test('attendance codes page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/AttendanceCodes.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Code|Title|Attendance/i);
  });

  test('attendance chart page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/DailySummary.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });

  test('absence summary page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/StudentSummary.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Absence|Student|Attendance/i);
  });

  test('teacher completion page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/TeacherCompletion.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Teacher|Attendance/i);
  });
});
