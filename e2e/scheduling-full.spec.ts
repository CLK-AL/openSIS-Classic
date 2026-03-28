import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Scheduling — Full Coverage', () => {
  test('student requests', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/Requests.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('group requests', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/MassRequests.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('group drops', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/MassDrops.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('group delete', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/MassDelete.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('schoolwide schedule report', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/SchoolwideScheduleReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print class pictures', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/PrintClassPictures.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print requests', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/PrintRequests.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('schedule report', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/ScheduleReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('requests report', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/RequestsReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('unfilled requests', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/UnfilledRequests.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('add/drop report', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/AddDrop.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('run scheduler', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/Scheduler.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('student schedule report', async ({ adminPage: page }) => {
    await navigateTo(page, 'scheduling/StudentScheduleReport.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
