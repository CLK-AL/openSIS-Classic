import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('School Setup Module', () => {

  test('school information page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Schools.php');
    await expect(page.locator('.content-wrapper')).toContainText(/School/i);
  });

  test('marking periods page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/MarkingPeriods.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Year|Semester|Quarter/i);
  });

  test('calendars page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Calendar.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });

  test('periods page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Periods.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Period|Start Time|End Time/i);
  });

  test('grade levels page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/GradeLevels.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Grade Level/i);
  });

  test('rooms page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Rooms.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Room|Capacity/i);
  });

  test('course manager page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Courses.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Course|Subject/i);
  });

  test('system preferences page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/SystemPreference.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Maintenance|Currency|Half/i);
  });
});
