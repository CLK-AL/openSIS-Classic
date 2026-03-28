import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Grades Module', () => {

  test('report cards page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/ReportCards.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Report Card|Student/i);
  });

  test('transcripts page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/Transcripts.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Transcript|Student/i);
  });

  test('teacher completion page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/TeacherCompletion.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Teacher|Grade/i);
  });

  test('GPA class rank page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/GPARankList.php');
    await expect(page.locator('.content-wrapper')).toContainText(/GPA|Rank|Student/i);
  });

  test('report card grades setup loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/ReportCardGrades.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Grade|Scale|Break/i);
  });

  test('report card comments setup loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/ReportCardComments.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Comment|Code/i);
  });

  test('honor roll setup loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/HonorRollSetup.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
