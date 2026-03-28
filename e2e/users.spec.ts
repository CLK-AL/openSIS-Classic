import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Users Module', () => {

  test('preferences page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/Preferences.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Display|Date|Student/i);
  });

  test('staff info page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/Staff.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Staff|Find|Search/i);
  });

  test('add staff form loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/Staff.php&staff_id=new');
    await expect(page.locator('.content-wrapper')).toContainText(/First Name|Last Name/i);
  });

  test('profiles page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/Profiles.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Profile|Permission/i);
  });

  test('parent info page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/User.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Parent|Find/i);
  });

  test('staff fields page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'users/StaffFields.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Field|Category/i);
  });
});
