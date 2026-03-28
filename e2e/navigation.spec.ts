import { test, expect } from './fixtures';

test.describe('Sidebar Navigation', () => {

  test('sidebar shows all admin modules', async ({ adminPage: page }) => {
    const sidebar = page.locator('.navigation-main');
    await expect(sidebar.locator('span').filter({ hasText: 'Home' }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /School/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Student/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /User/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Scheduling/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Grades/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Attendance/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Messaging/ }).first()).toBeVisible();
    await expect(sidebar.locator('span').filter({ hasText: /Tools/ }).first()).toBeVisible();
  });

  test('user profile shown in sidebar', async ({ adminPage: page }) => {
    const profile = page.locator('.sidebar-user-material-content');
    await expect(profile.locator('h6')).toBeVisible();
    await expect(profile.locator('span')).toContainText(/Admin/i);
  });

  test('My Account dropdown works', async ({ adminPage: page }) => {
    await page.locator('.sidebar-user-material-menu a').click();
    const userNav = page.locator('#user-nav');
    await expect(userNav.locator('span').filter({ hasText: /Messages/ }).first()).toBeVisible();
    await expect(userNav.locator('span').filter({ hasText: /Preferences/ }).first()).toBeVisible();
    await expect(userNav.locator('span').filter({ hasText: /Logout/ }).first()).toBeVisible();
  });

  test('clicking Home navigates to portal', async ({ adminPage: page }) => {
    await page.locator('.navigation-main a span').filter({ hasText: 'Home' }).first().click();
    await page.waitForTimeout(1000);
    // Portal page should load
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
