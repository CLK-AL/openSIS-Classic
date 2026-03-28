<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Integration test: DBGet workflow with TSV mock data.
 *
 * Tests the full data retrieval pipeline: TSV data → db_fetch_row → DBGet
 * with indexing, formatting functions, and multi-result handling.
 */
class DBGetWorkflowTest extends TestCase
{
    public function testStudentListRetrieval(): void
    {
        $tsv = "STUDENT_ID\tFIRST_NAME\tLAST_NAME\tGRADE\n";
        $tsv .= "101\tAlice\tSmith\t10\n";
        $tsv .= "102\tBob\tJones\t11\n";
        $tsv .= "103\tCharlie\tBrown\t10\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $students = DBGet($rs);

        $this->assertCount(3, $students);
        $this->assertEquals('Alice', $students[1]['FIRST_NAME']);
        $this->assertEquals('Jones', $students[2]['LAST_NAME']);
        $this->assertEquals('10', $students[3]['GRADE']);
    }

    public function testCoursePeriodsIndexedByCourseId(): void
    {
        $tsv = "COURSE_ID\tTITLE\tPERIOD\n";
        $tsv .= "10\tAlgebra\t1\n";
        $tsv .= "10\tAlgebra\t2\n";
        $tsv .= "20\tBiology\t3\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $result = DBGet($rs, [], ['COURSE_ID']);

        $this->assertArrayHasKey('10', $result);
        $this->assertArrayHasKey('20', $result);
        $this->assertCount(2, $result['10']);
        $this->assertCount(1, $result['20']);
        $this->assertEquals('1', $result['10'][1]['PERIOD']);
        $this->assertEquals('2', $result['10'][2]['PERIOD']);
    }

    public function testGradeReportWithFormattingFunction(): void
    {
        if (!function_exists('_format_percent')) {
            function _format_percent($value, $key) {
                return round((float)$value * 100) . '%';
            }
        }

        $tsv = "STUDENT_ID\tNAME\tGPA\n";
        $tsv .= "101\tAlice\t0.95\n";
        $tsv .= "102\tBob\t0.82\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $result = DBGet($rs, ['GPA' => '_format_percent']);

        $this->assertEquals('95%', $result[1]['GPA']);
        $this->assertEquals('82%', $result[2]['GPA']);
        $this->assertEquals('Alice', $result[1]['NAME']); // Unformatted
    }

    public function testAttendanceRecordsByDate(): void
    {
        $tsv = "STUDENT_ID\tSCHOOL_DATE\tATTENDANCE_CODE\n";
        $tsv .= "101\t2025-09-01\tP\n";
        $tsv .= "101\t2025-09-02\tA\n";
        $tsv .= "102\t2025-09-01\tP\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $records = DBGet($rs, [], ['STUDENT_ID']);

        $this->assertEquals('P', $records['101'][1]['ATTENDANCE_CODE']);
        $this->assertEquals('A', $records['101'][2]['ATTENDANCE_CODE']);
        $this->assertEquals('P', $records['102'][1]['ATTENDANCE_CODE']);
    }

    public function testScheduleWithMultiLevelIndex(): void
    {
        $tsv = "COURSE_ID\tPERIOD_ID\tSTUDENT_ID\tNAME\n";
        $tsv .= "10\t1\t101\tAlice\n";
        $tsv .= "10\t1\t102\tBob\n";
        $tsv .= "10\t2\t103\tCharlie\n";
        $tsv .= "20\t1\t101\tAlice\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $result = DBGet($rs, [], ['COURSE_ID', 'PERIOD_ID']);

        $this->assertEquals('Alice', $result['10']['1'][1]['NAME']);
        $this->assertEquals('Bob', $result['10']['1'][2]['NAME']);
        $this->assertEquals('Charlie', $result['10']['2'][1]['NAME']);
        $this->assertEquals('Alice', $result['20']['1'][1]['NAME']);
    }

    public function testEmptyResultSet(): void
    {
        $rs = TsvResultSet::empty();
        $result = DBGet($rs);
        $this->assertEmpty($result);
    }

    public function testSingleRowResult(): void
    {
        $rs = TsvResultSet::fromArray([
            ['COUNT' => '42']
        ]);
        $result = DBGet($rs);
        $this->assertEquals('42', $result[1]['COUNT']);
    }

    public function testEnrollmentCodesLookup(): void
    {
        $tsv = "ID\tTITLE\tSHORT_NAME\tTYPE\n";
        $tsv .= "1\tNew Student\tNEW\tAdd\n";
        $tsv .= "2\tReturning\tRET\tAdd\n";
        $tsv .= "3\tTransferred In\tTRAN\tAdd\n";
        $tsv .= "4\tDropped Out\tDROP\tDrop\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $codes = DBGet($rs, [], ['TYPE']);

        $this->assertCount(3, $codes['Add']);
        $this->assertCount(1, $codes['Drop']);
        $this->assertEquals('TRAN', $codes['Add'][3]['SHORT_NAME']);
    }

    public function testGradeScaleData(): void
    {
        $tsv = "ID\tTITLE\tBREAK_OFF\tGP_VALUE\n";
        $tsv .= "1\tA\t90\t4.0\n";
        $tsv .= "2\tB\t80\t3.0\n";
        $tsv .= "3\tC\t70\t2.0\n";
        $tsv .= "4\tD\t60\t1.0\n";
        $tsv .= "5\tF\t0\t0.0\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $grades = DBGet($rs);

        $this->assertCount(5, $grades);
        $this->assertEquals('A', $grades[1]['TITLE']);
        $this->assertEquals('90', $grades[1]['BREAK_OFF']);
        $this->assertEquals('0.0', $grades[5]['GP_VALUE']);
    }

    public function testMarkingPeriodHierarchy(): void
    {
        $tsv = "MARKING_PERIOD_ID\tMP_TYPE\tTITLE\tSTART_DATE\tEND_DATE\n";
        $tsv .= "1\tFY\tFull Year\t2025-08-15\t2026-06-15\n";
        $tsv .= "2\tSEM\tSemester 1\t2025-08-15\t2025-12-20\n";
        $tsv .= "3\tSEM\tSemester 2\t2026-01-05\t2026-06-15\n";
        $tsv .= "4\tQTR\tQuarter 1\t2025-08-15\t2025-10-15\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $periods = DBGet($rs, [], ['MP_TYPE']);

        $this->assertCount(1, $periods['FY']);
        $this->assertCount(2, $periods['SEM']);
        $this->assertCount(1, $periods['QTR']);
        $this->assertEquals('Full Year', $periods['FY'][1]['TITLE']);
    }

    public function testReportCardGradesWithGradeScale(): void
    {
        $tsv = "STUDENT_ID\tCOURSE_TITLE\tGRADE_LETTER\tGP_VALUE\tCREDIT\n";
        $tsv .= "101\tAlgebra\tA\t4.0\t1.0\n";
        $tsv .= "101\tBiology\tB\t3.0\t1.0\n";
        $tsv .= "101\tEnglish\tA\t4.0\t1.0\n";
        $tsv .= "102\tAlgebra\tC\t2.0\t1.0\n";

        $rs = TsvResultSet::fromTsv($tsv);
        $grades = DBGet($rs, [], ['STUDENT_ID']);

        // Student 101 has 3 courses
        $this->assertCount(3, $grades['101']);
        // Student 102 has 1 course
        $this->assertCount(1, $grades['102']);

        // Verify GPA calculation data
        $totalGP = 0;
        foreach ($grades['101'] as $g) {
            $totalGP += (float)$g['GP_VALUE'];
        }
        $gpa = $totalGP / count($grades['101']);
        $this->assertEqualsWithDelta(3.67, $gpa, 0.01);
    }
}
