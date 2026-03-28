import { test, expect } from './fixtures';
import { navigateTo } from './fixtures';

test.describe('Students Module', () => {

  test('student search page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/Student.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Student|Search|Find/i);
  });

  test('add student form loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/Student.php&include=GeneralInfoInc&student_id=new');
    await expect(page.locator('.content-wrapper')).toContainText(/First Name|Last Name|Gender/i);
  });

  test('student fields setup page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/StudentFields.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Field|Category/i);
  });

  test('enrollment codes page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/EnrollmentCodes.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Enrollment|Code/i);
  });

  test('advanced report page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/AdvancedReport.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Report|Field/i);
  });

  test('group assign page loads', async ({ adminPage: page }) => {
    await navigateTo(page, 'students/AssignOtherInfo.php');
    await expect(page.locator('.content-wrapper')).toContainText(/Assign|Student/i);
  });
});
