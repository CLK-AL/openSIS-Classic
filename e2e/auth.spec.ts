import { test, expect } from '@playwright/test';
import { login, ADMIN_USER, ADMIN_PASS } from './fixtures';

test.describe('Authentication', () => {

  test('login page loads with correct elements', async ({ page }) => {
    await page.goto('/index.php');
    await expect(page.locator('#username')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('button[name="log"]')).toBeVisible();
    await expect(page.locator('#language')).toBeVisible();
    await expect(page.locator('#remember')).toBeVisible();
    await expect(page.locator('#forgotPass')).toBeVisible();
  });

  test('login page shows openSIS branding', async ({ page }) => {
    await page.goto('/index.php');
    await expect(page.locator('img[alt="openSIS"]')).toBeVisible();
    await expect(page.locator('.panel-heading h3')).toContainText('Student Information System');
  });

  test('successful admin login redirects to portal', async ({ page }) => {
    await login(page, ADMIN_USER, ADMIN_PASS);
    await expect(page.locator('.navigation-main')).toBeVisible();
    await expect(page.locator('.sidebar-user-material-content h6')).toBeVisible();
  });

  test('failed login shows error message', async ({ page }) => {
    await page.goto('/index.php');
    await page.locator('#username').fill('wronguser');
    await page.locator('#password').fill('wrongpass');
    await page.locator('button[name="log"]').click();
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('empty credentials show validation', async ({ page }) => {
    await page.goto('/index.php');
    await page.locator('button[name="log"]').click();
    // Should stay on login page or show error
    await expect(page.locator('#username')).toBeVisible();
  });

  test('language selector has all supported languages', async ({ page }) => {
    await page.goto('/index.php');
    const options = page.locator('#language option');
    await expect(options).toHaveCount(5); // en, fr, es, ar, he
    await expect(options.nth(0)).toHaveText('English');
  });

  test('language switch to Arabic sets RTL', async ({ page }) => {
    await page.goto('/index.php?language=ar');
    const html = page.locator('html');
    await expect(html).toHaveAttribute('dir', 'rtl');
  });

  test('language switch to Hebrew sets RTL', async ({ page }) => {
    await page.goto('/index.php?language=he');
    const html = page.locator('html');
    await expect(html).toHaveAttribute('dir', 'rtl');
  });

  test('forgot password link navigates correctly', async ({ page }) => {
    await page.goto('/index.php');
    await page.locator('#forgotPass').click();
    await expect(page).toHaveURL(/ForgotPass\.php/);
  });

  test('logout returns to login page', async ({ page }) => {
    await login(page, ADMIN_USER, ADMIN_PASS);
    await page.goto('/index.php?modfunc=logout');
    await expect(page.locator('#username')).toBeVisible();
  });
});
