import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Scheduling Module', () => {

  test('student schedule page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/Schedule.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Schedule|Student|Course/i);
  });

  test('view schedule page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/ViewSchedule.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Schedule/i);
  });

  test('group schedule page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/MassSchedule.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Course|Schedule|Student/i);
  });

  test('print schedules page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/PrintSchedules.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Print|Schedule|Student/i);
  });

  test('print class lists page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/PrintClassLists.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Class|List|Course/i);
  });

  test('incomplete schedules page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/IncompleteSchedules.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
