import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Tools Module', () => {

  test('access log page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/LogDetails.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Log|Login|Record/i);
  });

  test('backup page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Backup.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Backup|Database/i);
  });

  test('data import page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/DataImport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Import|Data|Student|Staff/i);
  });

  test('API token page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/GenerateApi.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Token|Key|Secret/i);
  });

  test('translation manager page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/TranslationManager.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Translation|Language|Import/i);
  });

  test('rollover page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Rollover.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Rollover|School Year/i);
  });

  test('at a glance report loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/Reports.php?func=Basic');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|School|Student/i);
  });

  test('vCard export page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/VCardExport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/vCard|Export|Student|Staff|Parent/i);
  });

  test('vCard export has three export buttons', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/VCardExport.php');
    const exportBtns = page.locator('button').filter({ hasText: /Export.*\.vcf/i });
    await expect(exportBtns).toHaveCount(3); // students, staff, parents
  });

  test('vCard import form exists', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/VCardExport.php');
    await expect(page.locator('input[name="vcf_file"]')).toBeVisible();
  });

  test('iCal export page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/ICalExport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/iCal|Export|Event|Calendar/i);
  });

  test('iCal export has three export buttons', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/ICalExport.php');
    const exportBtns = page.locator('button').filter({ hasText: /Export.*\.ics/i });
    await expect(exportBtns).toHaveCount(3); // events, school days, marking periods
  });

  test('iCal import form exists', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/ICalExport.php');
    await expect(page.locator('input[name="ics_file"]')).toBeVisible();
  });

  test('delete log page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'tools/DeleteLog.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Delete|Log/i);
  });
});
