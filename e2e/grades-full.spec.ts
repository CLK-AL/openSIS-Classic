import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Grades — Full Coverage', () => {
  test('input final grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/InputFinalGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('student grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/StudentGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('final grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/FinalGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('grade breakdown', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/GradeBreakdown.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('progress reports (admin)', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/AdminProgressReports.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('progress reports (teacher)', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/ProgressReports.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('parent progress reports', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/ParentProgressReports.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('honor roll', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/HonorRoll.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('configuration', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/Configuration.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('edit report card grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/EditReportCardGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('edit history marking periods', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/EditHistoryMarkingPeriods.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('historical report card grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/HistoricalReportCardGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('anomalous grades', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/AnomalousGrades.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
  test('calculate GPA', async ({ adminPage: page }) => {
    await navigateTo(page, 'grades/CalcGPA.php');
    await expect(page.locator('.content-wrapper')).toBeVisible();
  });
});
