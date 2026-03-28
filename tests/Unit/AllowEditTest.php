<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/AllowEditFnc.php';

class AllowEditTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS;
        $_openSIS = [];
        $_REQUEST = [];
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['UserSyear'] = '2025';
    }

    protected function tearDown(): void
    {
        global $_openSIS;
        $_openSIS = [];
        $_REQUEST = [];
    }

    private function setUserProfile(string $profile, string $profileId = '1'): void
    {
        global $_openSIS;
        $_openSIS['User'] = [1 => [
            'PROFILE' => $profile,
            'PROFILE_ID' => $profileId,
            'STAFF_ID' => '1',
            'SYEAR' => '2025',
        ]];
    }

    public function testAdminWithAllowedModule(): void
    {
        $this->setUserProfile('admin', '1');
        global $_openSIS;
        $_openSIS['AllowEdit'] = ['students/Student.php' => [1 => ['MODNAME' => 'students/Student.php']]];

        $this->assertTrue(AllowEdit('students/Student.php'));
    }

    public function testAdminWithDisallowedModule(): void
    {
        $this->setUserProfile('admin', '1');
        global $_openSIS;
        $_openSIS['AllowEdit'] = ['students/Student.php' => [1 => ['MODNAME' => 'students/Student.php']]];

        $this->assertFalse(AllowEdit('tools/Backup.php'));
    }

    public function testParentCanViewAttendanceSummary(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;

        $this->assertTrue(AllowEdit('attendance/StudentSummary.php'));
    }

    public function testParentCanViewCalendar(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;

        $this->assertTrue(AllowEdit('schoolsetup/Calendar.php'));
    }

    public function testParentCanViewSchedule(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;

        $this->assertTrue(AllowEdit('scheduling/ViewSchedule.php'));
    }

    public function testParentCannotEditStudents(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;
        global $_openSIS;
        $_openSIS['allow_edit'] = false;

        $this->assertFalse(AllowEdit('students/Student.php'));
    }

    public function testStudentCanViewAttendanceSummary(): void
    {
        $this->setUserProfile('student', '3');
        $_SESSION['PROFILE_ID'] = 3;

        $this->assertTrue(AllowEdit('attendance/StudentSummary.php'));
    }

    public function testTeacherCanTakeAttendance(): void
    {
        $this->setUserProfile('teacher', '2');
        $this->assertTrue(AllowEdit('attendance/TakeAttendance.php'));
    }

    public function testTeacherCanEditGrades(): void
    {
        $this->setUserProfile('teacher', '2');
        $this->assertTrue(AllowEdit('grades/Grades.php'));
    }

    public function testTeacherCanEditAssignments(): void
    {
        $this->setUserProfile('teacher', '2');
        $this->assertTrue(AllowEdit('grades/Assignments.php'));
    }

    public function testTeacherCanViewSchedule(): void
    {
        $this->setUserProfile('teacher', '2');
        $this->assertTrue(AllowEdit('scheduling/ViewSchedule.php'));
    }

    public function testTeacherCanViewAttendanceSummary(): void
    {
        $this->setUserProfile('teacher', '2');
        $this->assertTrue(AllowEdit('attendance/StudentSummary.php'));
    }

    public function testParentCanAccessMessagingGroups(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;

        $this->assertTrue(AllowEdit('messaging/Group.php'));
    }

    public function testParentCanViewDailySummary(): void
    {
        $this->setUserProfile('parent', '4');
        $_SESSION['PROFILE_ID'] = 4;

        $this->assertTrue(AllowEdit('attendance/DailySummary.php'));
    }
}
