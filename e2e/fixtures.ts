import { test as base, expect, Page } from '@playwright/test';

/** Default admin credentials — override via environment variables. */
export const ADMIN_USER = process.env.TEST_ADMIN_USER || 'admin';
export const ADMIN_PASS = process.env.TEST_ADMIN_PASS || 'admin';
export const TEACHER_USER = process.env.TEST_TEACHER_USER || 'teacher';
export const TEACHER_PASS = process.env.TEST_TEACHER_PASS || 'teacher';

/** Log in to openSIS and return the authenticated page. */
export async function login(page: Page, username: string, password: string): Promise<void> {
  await page.goto('/index.php');
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.locator('button[name="log"]').click();
  // Wait for main app to load (sidebar with navigation)
  await page.waitForSelector('.navigation-main', { timeout: 15_000 });
}

/** Navigate to a module via the AJAX loader used by openSIS. */
export async function navigateTo(page: Page, modname: string): Promise<void> {
  await page.evaluate((mod) => {
    (window as any).check_content(`Ajax.php?modname=${mod}`);
  }, modname);
  // Wait for the content area to update
  await page.waitForTimeout(1000);
  await page.waitForLoadState('networkidle');
}

/** Click a sidebar menu item by its visible text. */
export async function clickSidebarMenu(page: Page, text: string): Promise<void> {
  await page.locator('.navigation-main a span').filter({ hasText: text }).first().click();
  await page.waitForTimeout(500);
}

/** Click a sub-menu item within an expanded sidebar section. */
export async function clickSubMenu(page: Page, text: string): Promise<void> {
  await page.locator('.navigation-main li li a').filter({ hasText: text }).first().click();
  await page.waitForTimeout(800);
  await page.waitForLoadState('networkidle');
}

/** Get the content area text (the main module output area). */
export async function getContentText(page: Page): Promise<string> {
  return await page.locator('.content-wrapper').innerText();
}

/** Extend base test with auto-login as admin. */
export const test = base.extend<{ adminPage: Page }>({
  adminPage: async ({ page }, use) => {
    await login(page, ADMIN_USER, ADMIN_PASS);
    await use(page);
  },
});

export { expect };
