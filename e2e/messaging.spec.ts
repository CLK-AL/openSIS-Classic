import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Messaging Module', () => {

  test('inbox page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'messaging/Inbox.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Inbox|Message/i);
  });

  test('compose page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'messaging/Compose.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Compose|Send|Message/i);
  });

  test('sent messages page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'messaging/SentMail.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Sent|Message/i);
  });

  test('trash page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'messaging/Trash.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });

  test('groups page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'messaging/Group.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Group/i);
  });
});
