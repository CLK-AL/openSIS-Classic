import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('School Setup — Full Coverage', () => {
  test('portal notes', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/PortalNotes.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('copy school', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/CopySchool.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('course catalog', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/CourseCatalog.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print all courses', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/PrintAllCourses.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print catalog by term', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/PrintCatalog.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('print catalog by grade level', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/PrintCatalogGradeLevel.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('school custom fields', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/SchoolCustomFields.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('sections', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Sections.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('teacher reassignment', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/TeacherReassignment.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('add a school', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Schools.php?new_school=true');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('rollover', async ({ adminPage: page }) => {
    await navigateTo(page, 'schoolsetup/Rollover.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
