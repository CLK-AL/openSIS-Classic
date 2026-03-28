import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Attendance — Full Coverage', () => {
  test('add absences', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/AddAbsences.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('attendance report', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/AttendanceData.php?list_by_day=true');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('average daily attendance', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/Percent.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('average attendance by day', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/Percent.php?list_by_day=true');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('recalculate daily attendance', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/FixDailyAttendance.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('delete duplicate attendance', async ({ adminPage: page }) => {
    await navigateTo(page, 'attendance/DuplicateAttendance.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
