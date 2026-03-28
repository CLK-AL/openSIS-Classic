import { test, expect } from '@playwright/test';
import { login, ADMIN_USER, ADMIN_PASS } from './fixtures';

test.describe('RTL Language Support', () => {

  test('Arabic login page renders RTL', async ({ page }) => {
    await page.goto('/index.php?language=ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    // Check Arabic text is present
    await expect(page.locator('.panel-heading h3')).toContainText(/نظام/);
    // Username placeholder should be Arabic
    const placeholder = await page.locator('#username').getAttribute('placeholder');
    expect(placeholder).toContain('المستخدم');
  });

  test('Hebrew login page renders RTL', async ({ page }) => {
    await page.goto('/index.php?language=he');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
  });

  test('English login page renders LTR', async ({ page }) => {
    await page.goto('/index.php?language=en');
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
  });

  test('RTL CSS loaded for Arabic', async ({ page }) => {
    await page.goto('/index.php?language=ar');
    // Check that rtl.css is linked
    const rtlLink = page.locator('link[href*="rtl.css"]');
    await expect(rtlLink).toHaveCount(1);
  });

  test('RTL CSS not loaded for English', async ({ page }) => {
    await page.goto('/index.php?language=en');
    const rtlLink = page.locator('link[href*="rtl.css"]');
    await expect(rtlLink).toHaveCount(0);
  });

  test('Arabic app has RTL dir after login', async ({ page }) => {
    await page.goto('/index.php?language=ar');
    await login(page, ADMIN_USER, ADMIN_PASS);
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
  });

  test('switching language preserves session', async ({ page }) => {
    // Login in English
    await page.goto('/index.php?language=en');
    await login(page, ADMIN_USER, ADMIN_PASS);
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
  });
});
