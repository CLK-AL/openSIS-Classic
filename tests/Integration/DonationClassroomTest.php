<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../SqliteTestDb.php';
require_once TEST_ROOT . '/functions/VCardFnc.php';
require_once TEST_ROOT . '/functions/ICalFnc.php';

/**
 * Full E2E simulation: Donation-Based Classroom
 *
 * Simulates the complete lifecycle of a school funded by donations,
 * from elementary through university, with 2 classes of 33 students:
 *
 * Phase 1: School setup (school, staff, 66 students, 2 classes)
 * Phase 2: Library setup (donated books, checkouts)
 * Phase 3: Lab equipment (donated equipment, maintenance)
 * Phase 4: Donations (money, books, equipment from various donors)
 * Phase 5: Student needs (tag needy students, fulfill from donations)
 * Phase 6: Field trips (plan, collect fees, execute)
 * Phase 7: Finance (track all money flows)
 * Phase 8: Grading (assignments, grades)
 * Phase 9: Attendance (daily records)
 * Phase 10: Reports and exports (vCard, iCal)
 */
class DonationClassroomTest extends TestCase
{
    private SqliteTestDb $db;
    private array $school;

    protected function setUp(): void
    {
        $this->db = new SqliteTestDb();
        $this->db->createSchema();
        $this->school = $this->db->seedSchool('Donation Academy', 2025, 2, 33);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 1: School Infrastructure
    // ═══════════════════════════════════════════════════════════════════

    public function testSchoolCreated(): void
    {
        $school = $this->db->queryOne("SELECT * FROM schools WHERE id = {$this->school['school_id']}");
        $this->assertEquals('Donation Academy', $school['title']);
        $this->assertEquals('Springfield', $school['city']);
    }

    public function testStaffCreated(): void
    {
        $teachers = $this->db->query("SELECT * FROM staff WHERE profile = 'teacher' AND current_school_id = {$this->school['school_id']}");
        $this->assertCount(2, $teachers);

        $admin = $this->db->queryOne("SELECT * FROM staff WHERE profile = 'admin' AND current_school_id = {$this->school['school_id']}");
        $this->assertNotNull($admin);
    }

    public function test66StudentsEnrolled(): void
    {
        $count = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM students");
        $this->assertEquals(66, (int)$count['cnt']);
    }

    public function testTwoClassesOf33(): void
    {
        $enrollments = $this->db->query("SELECT grade_id, COUNT(*) AS cnt FROM student_enrollment WHERE school_id = {$this->school['school_id']} GROUP BY grade_id ORDER BY grade_id");
        $this->assertCount(2, $enrollments);
        $this->assertEquals(33, (int)$enrollments[0]['cnt']);
        $this->assertEquals(33, (int)$enrollments[1]['cnt']);
    }

    public function testCoursesAndSchedules(): void
    {
        $courses = $this->db->query("SELECT * FROM courses WHERE school_id = {$this->school['school_id']}");
        $this->assertCount(2, $courses);

        $scheduled = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM schedule");
        $this->assertEquals(66, (int)$scheduled['cnt']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 2: Library — Donated Books
    // ═══════════════════════════════════════════════════════════════════

    public function testDonatedBooksAddedToLibrary(): void
    {
        $sid = $this->school['school_id'];
        $books = [
            ['Math for Everyone', 'J. Peterson', '978-0-123456-01', 10],
            ['Science Explorer', 'L. Darwin', '978-0-123456-02', 8],
            ['English Grammar', 'W. Shakespeare', '978-0-123456-03', 12],
            ['World History', 'H. Wells', '978-0-123456-04', 6],
            ['Art Fundamentals', 'P. Picasso', '978-0-123456-05', 5],
        ];

        $this->db->exec("INSERT INTO library_book_categories (school_id, title) VALUES ($sid, 'Textbook')");
        $catId = $this->db->lastInsertId();

        foreach ($books as $b) {
            $this->db->exec("INSERT INTO library_books (school_id, title, author, isbn, category_id, total_copies, available_copies)
                              VALUES ($sid, '{$b[0]}', '{$b[1]}', '{$b[2]}', $catId, {$b[3]}, {$b[3]})");
        }

        $count = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM library_books WHERE school_id = $sid");
        $this->assertEquals(5, (int)$count['cnt']);

        $totalCopies = $this->db->queryOne("SELECT SUM(total_copies) AS total FROM library_books WHERE school_id = $sid");
        $this->assertEquals(41, (int)$totalCopies['total']);
    }

    public function testBookCheckoutAndReturn(): void
    {
        $sid = $this->school['school_id'];
        $studentId = $this->school['student_ids'][0];

        $this->db->exec("INSERT INTO library_books (school_id, title, author, total_copies, available_copies) VALUES ($sid, 'Test Book', 'Author', 3, 3)");
        $bookId = $this->db->lastInsertId();

        // Checkout
        $this->db->exec("INSERT INTO library_checkout (book_id, school_id, borrower_type, borrower_id, checkout_date, due_date, status)
                          VALUES ($bookId, $sid, 'student', $studentId, '2025-09-01', '2025-09-15', 'checked_out')");
        $this->db->exec("UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id = $bookId");

        $book = $this->db->queryOne("SELECT available_copies FROM library_books WHERE book_id = $bookId");
        $this->assertEquals(2, (int)$book['available_copies']);

        // Return
        $this->db->exec("UPDATE library_checkout SET status = 'returned', return_date = '2025-09-10' WHERE book_id = $bookId AND borrower_id = $studentId");
        $this->db->exec("UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id = $bookId");

        $book = $this->db->queryOne("SELECT available_copies FROM library_books WHERE book_id = $bookId");
        $this->assertEquals(3, (int)$book['available_copies']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 3: Lab Equipment — Donated
    // ═══════════════════════════════════════════════════════════════════

    public function testDonatedEquipmentAdded(): void
    {
        $sid = $this->school['school_id'];
        $this->db->exec("INSERT INTO inventory_categories (school_id, title) VALUES ($sid, 'Lab Equipment')");
        $catId = $this->db->lastInsertId();

        $equipment = [
            ['Microscope', 'SN-001', 150.00, 10],
            ['Bunsen Burner', 'SN-002', 45.00, 15],
            ['Digital Scale', 'SN-003', 89.00, 5],
            ['Test Tube Set', 'SN-004', 25.00, 20],
        ];

        foreach ($equipment as $e) {
            $this->db->exec("INSERT INTO inventory_equipment (school_id, name, serial_number, category_id, purchase_cost, total_quantity, available_quantity, condition_status)
                              VALUES ($sid, '{$e[0]}', '{$e[1]}', $catId, {$e[2]}, {$e[3]}, {$e[3]}, 'New')");
        }

        $totalValue = $this->db->queryOne("SELECT SUM(purchase_cost * total_quantity) AS val FROM inventory_equipment WHERE school_id = $sid");
        $this->assertGreaterThan(3000, (float)$totalValue['val']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 4: Donations from Community
    // ═══════════════════════════════════════════════════════════════════

    public function testMultipleDonationTypes(): void
    {
        $sid = $this->school['school_id'];

        // Cash donation
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, donation_date, beneficiary_tag)
                          VALUES ($sid, 'money', 'Community Foundation', 'ngo', 'Annual Education Grant', 5000.00, '2025-08-01', 'scholarship')");

        // Book donation
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, quantity, donation_date)
                          VALUES ($sid, 'book', 'Local Library', 'community', '50 Used Textbooks', 50, '2025-08-10')");

        // Equipment donation
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, quantity, donation_date)
                          VALUES ($sid, 'equipment', 'Tech Corp', 'business', '10 Laptops', 3000.00, 10, '2025-08-15')");

        // Clothing donation for needy
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, quantity, donation_date, beneficiary_tag)
                          VALUES ($sid, 'clothing', 'Parent Association', 'parent', 'Winter Coats', 20, '2025-10-01', 'needy')");

        // Meal program donation
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, donation_date, beneficiary_tag)
                          VALUES ($sid, 'money', 'Rotary Club', 'community', 'Lunch Program Fund', 2500.00, '2025-09-01', 'meals')");

        $total = $this->db->queryOne("SELECT COUNT(*) AS cnt, SUM(monetary_value) AS val FROM inventory_donations WHERE school_id = $sid");
        $this->assertEquals(5, (int)$total['cnt']);
        $this->assertEquals(10500.00, (float)$total['val']);

        // Track in finance
        $this->db->exec("INSERT INTO inventory_finance (school_id, category, title, amount, payment_type, payment_date, status)
                          VALUES ($sid, 'Donation', 'Annual Education Grant', 5000, 'donation', '2025-08-01', 'paid')");
        $this->db->exec("INSERT INTO inventory_finance (school_id, category, title, amount, payment_type, payment_date, status)
                          VALUES ($sid, 'Donation', 'Lunch Program Fund', 2500, 'donation', '2025-09-01', 'paid')");
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 5: Needy Students — Tag and Fulfill
    // ═══════════════════════════════════════════════════════════════════

    public function testTagNeedyStudentsAndFulfill(): void
    {
        $sid = $this->school['school_id'];

        // Identify 5 students with needs
        $needyStudents = array_slice($this->school['student_ids'], 0, 5);
        $needTypes = ['Uniform', 'Books', 'Lunch/Meals', 'Shoes/Clothing', 'School Supplies'];
        $costs = [50, 30, 200, 40, 25];

        foreach ($needyStudents as $i => $studentId) {
            $this->db->exec("INSERT INTO inventory_student_needs (school_id, student_id, need_type, priority, estimated_cost, status, date_identified)
                              VALUES ($sid, $studentId, '{$needTypes[$i]}', 'high', {$costs[$i]}, 'open', '2025-09-01')");
        }

        $openNeeds = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM inventory_student_needs WHERE school_id = $sid AND status = 'open'");
        $this->assertEquals(5, (int)$openNeeds['cnt']);

        $totalNeeded = $this->db->queryOne("SELECT SUM(estimated_cost) AS total FROM inventory_student_needs WHERE school_id = $sid AND status = 'open'");
        $this->assertEquals(345.00, (float)$totalNeeded['total']);

        // Create a donation to fulfill some needs
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, donation_date, beneficiary_tag)
                          VALUES ($sid, 'money', 'Alumni Association', 'alumni', 'Student Aid Fund', 500.00, '2025-09-15', 'needy')");
        $donationId = $this->db->lastInsertId();

        // Fulfill 3 needs from this donation
        for ($i = 0; $i < 3; $i++) {
            $this->db->exec("UPDATE inventory_student_needs SET status = 'fulfilled', funded_amount = estimated_cost,
                              funded_by_donation_id = $donationId, date_fulfilled = '2025-09-20'
                              WHERE school_id = $sid AND student_id = {$needyStudents[$i]}");
        }

        $fulfilled = $this->db->queryOne("SELECT COUNT(*) AS cnt, SUM(funded_amount) AS total FROM inventory_student_needs WHERE school_id = $sid AND status = 'fulfilled'");
        $this->assertEquals(3, (int)$fulfilled['cnt']);
        $this->assertEquals(280.00, (float)$fulfilled['total']); // 50+30+200

        $stillOpen = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM inventory_student_needs WHERE school_id = $sid AND status = 'open'");
        $this->assertEquals(2, (int)$stillOpen['cnt']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 6: Field Trip — Donation Funded
    // ═══════════════════════════════════════════════════════════════════

    public function testFieldTripFundedByDonations(): void
    {
        $sid = $this->school['school_id'];

        $this->db->exec("INSERT INTO inventory_field_trips (school_id, title, destination, trip_date, return_date, cost_per_student, total_budget, status)
                          VALUES ($sid, 'Science Museum Visit', 'Springfield Science Museum', '2025-11-15', '2025-11-15', 15.00, 990.00, 'planned')");
        $tripId = $this->db->lastInsertId();

        // Donation covers the trip
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, donation_date)
                          VALUES ($sid, 'money', 'PTA', 'parent', 'Museum Trip Fund', 990.00, '2025-10-01')");

        $this->db->exec("UPDATE inventory_field_trips SET collected_amount = 990.00, status = 'confirmed' WHERE id = $tripId");

        $trip = $this->db->queryOne("SELECT * FROM inventory_field_trips WHERE id = $tripId");
        $this->assertEquals('confirmed', $trip['status']);
        $this->assertEquals(990.00, (float)$trip['collected_amount']);
        $this->assertEquals(990.00, (float)$trip['total_budget']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 7: Finance — Track All Flows
    // ═══════════════════════════════════════════════════════════════════

    public function testFinancialTracking(): void
    {
        $sid = $this->school['school_id'];

        // Record various financial transactions
        $transactions = [
            ['Donation', 'Community Grant', 5000, 'donation', 'paid'],
            ['Donation', 'Lunch Program', 2500, 'donation', 'paid'],
            ['Lab Fee', 'Chemistry Lab Fee - Class 1', 330, 'collection', 'paid'],
            ['Book Fee', 'Textbook Fee - Class 2', 495, 'collection', 'paid'],
            ['Field Trip', 'Museum Trip', 990, 'collection', 'paid'],
            ['Supplies', 'Art Supplies Bulk', -250, 'transfer', 'paid'],
        ];

        foreach ($transactions as $t) {
            $this->db->exec("INSERT INTO inventory_finance (school_id, category, title, amount, payment_type, payment_date, status)
                              VALUES ($sid, '{$t[0]}', '{$t[1]}', {$t[2]}, '{$t[3]}', '2025-09-01', '{$t[4]}')");
        }

        $totals = $this->db->queryOne("SELECT SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) AS expenses,
            SUM(amount) AS net
            FROM inventory_finance WHERE school_id = $sid");

        $this->assertEquals(9315.00, (float)$totals['income']);
        $this->assertEquals(250.00, (float)$totals['expenses']);
        $this->assertEquals(9065.00, (float)$totals['net']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 8: Grading
    // ═══════════════════════════════════════════════════════════════════

    public function testGradingWorkflow(): void
    {
        $sid = $this->school['school_id'];

        // Get course period for class 1
        $cp = $this->db->queryOne("SELECT course_period_id FROM course_periods LIMIT 1");
        $cpId = $cp['course_period_id'];

        // Create assignment
        $this->db->exec("INSERT INTO gradebook_assignments (course_period_id, title, assigned_date, due_date, points)
                          VALUES ($cpId, 'Midterm Exam', '2025-10-15', '2025-10-15', 100)");
        $assignId = $this->db->lastInsertId();

        // Grade 33 students
        $students = array_slice($this->school['student_ids'], 0, 33);
        foreach ($students as $i => $sid) {
            $score = max(40, min(100, 70 + ($i * 2) - 33)); // range ~40-100
            $this->db->exec("INSERT INTO gradebook_grades (student_id, assignment_id, points) VALUES ($sid, $assignId, $score)");
        }

        $stats = $this->db->queryOne("SELECT COUNT(*) AS cnt, AVG(points) AS avg, MIN(points) AS min, MAX(points) AS max
                                       FROM gradebook_grades WHERE assignment_id = $assignId");
        $this->assertEquals(33, (int)$stats['cnt']);
        $this->assertGreaterThan(50, (float)$stats['avg']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 9: Attendance
    // ═══════════════════════════════════════════════════════════════════

    public function testAttendanceTracking(): void
    {
        $cp = $this->db->queryOne("SELECT course_period_id FROM course_periods LIMIT 1");
        $cpId = $cp['course_period_id'];
        $students = array_slice($this->school['student_ids'], 0, 33);

        // Record a week of attendance
        $dates = ['2025-09-01', '2025-09-02', '2025-09-03', '2025-09-04', '2025-09-05'];
        foreach ($dates as $date) {
            foreach ($students as $i => $sid) {
                $code = ($i % 10 === 0 && $date === '2025-09-03') ? 'A' : 'P'; // ~10% absent on Wed
                $this->db->exec("INSERT INTO attendance_period (student_id, course_period_id, school_date, attendance_code)
                                  VALUES ($sid, $cpId, '$date', '$code')");
            }
        }

        $totalRecords = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM attendance_period");
        $this->assertEquals(165, (int)$totalRecords['cnt']); // 33 * 5

        $absences = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM attendance_period WHERE attendance_code = 'A'");
        $this->assertGreaterThan(0, (int)$absences['cnt']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Phase 10: Exports
    // ═══════════════════════════════════════════════════════════════════

    public function testVCardExportStudents(): void
    {
        $students = $this->db->query("SELECT * FROM students LIMIT 5");
        $vcf = '';
        foreach ($students as $s) {
            $vcf .= buildVCard([
                'first' => $s['first_name'], 'last' => $s['last_name'],
                'categories' => 'Student',
            ]);
        }
        $this->assertStringContainsString('BEGIN:VCARD', $vcf);
        $this->assertEquals(5, substr_count($vcf, 'BEGIN:VCARD'));

        // Round-trip parse
        $parsed = parseVCards($vcf);
        $this->assertCount(5, $parsed);
    }

    public function testICalExportFieldTrips(): void
    {
        $sid = $this->school['school_id'];
        $this->db->exec("INSERT INTO inventory_field_trips (school_id, title, destination, trip_date, cost_per_student, status)
                          VALUES ($sid, 'Zoo Visit', 'City Zoo', '2025-12-01', 10, 'confirmed')");

        $trips = $this->db->query("SELECT * FROM inventory_field_trips WHERE school_id = $sid");
        $cal = icalHeader('Field Trips');
        foreach ($trips as $t) {
            $cal .= buildEvent([
                'uid' => 'trip-' . $t['id'] . '@test',
                'summary' => $t['title'] . ' - ' . $t['destination'],
                'date' => $t['trip_date'], 'allday' => true,
                'alarms' => [1440, 60],
            ]);
        }
        $cal .= "END:VCALENDAR\r\n";

        $this->assertStringContainsString('SUMMARY:Zoo Visit - City Zoo', $cal);
        $this->assertStringContainsString('BEGIN:VALARM', $cal);
        $this->assertEquals(2, substr_count($cal, 'BEGIN:VALARM'));
    }

    public function testICalExportBirthdaysTodo(): void
    {
        $students = $this->db->query("SELECT * FROM students WHERE birthdate IS NOT NULL LIMIT 3");
        $cal = icalHeader('Birthdays');
        foreach ($students as $s) {
            $cal .= buildTodo([
                'uid' => 'bday-' . $s['student_id'] . '@test',
                'summary' => $s['first_name'] . ' ' . $s['last_name'] . ' Birthday',
                'due' => date('Y') . substr($s['birthdate'], 4),
                'priority' => 5,
                'status' => 'NEEDS-ACTION',
                'alarms' => [1440],
            ]);
        }
        $cal .= "END:VCALENDAR\r\n";

        $this->assertEquals(3, substr_count($cal, 'BEGIN:VTODO'));
        $this->assertStringContainsString('Birthday', $cal);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Cross-Phase Verification
    // ═══════════════════════════════════════════════════════════════════

    public function testDonationToNeedsPipeline(): void
    {
        $sid = $this->school['school_id'];
        $needyId = $this->school['student_ids'][5];

        // 1. Tag need
        $this->db->exec("INSERT INTO inventory_student_needs (school_id, student_id, need_type, priority, estimated_cost, status, date_identified)
                          VALUES ($sid, $needyId, 'Books', 'critical', 75.00, 'open', '2025-09-01')");
        $needId = $this->db->lastInsertId();

        // 2. Receive donation
        $this->db->exec("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, title, monetary_value, donation_date, beneficiary_tag, beneficiary_student_id)
                          VALUES ($sid, 'money', 'Kind Parent', 'parent', 'Books for student', 75.00, '2025-09-10', 'needy', $needyId)");
        $donId = $this->db->lastInsertId();

        // 3. Record in finance
        $this->db->exec("INSERT INTO inventory_finance (school_id, category, title, student_id, amount, payment_type, payment_date, status, reference_type)
                          VALUES ($sid, 'Donation', 'Books for student', $needyId, 75.00, 'donation', '2025-09-10', 'paid', 'donation')");

        // 4. Fulfill need
        $this->db->exec("UPDATE inventory_student_needs SET status = 'fulfilled', funded_amount = 75.00,
                          funded_by_donation_id = $donId, date_fulfilled = '2025-09-12' WHERE id = $needId");

        // Verify complete pipeline
        $need = $this->db->queryOne("SELECT * FROM inventory_student_needs WHERE id = $needId");
        $this->assertEquals('fulfilled', $need['status']);
        $this->assertEquals(75.00, (float)$need['funded_amount']);
        $this->assertEquals($donId, (int)$need['funded_by_donation_id']);

        $donation = $this->db->queryOne("SELECT * FROM inventory_donations WHERE id = $donId");
        $this->assertEquals($needyId, (int)$donation['beneficiary_student_id']);

        $finance = $this->db->queryOne("SELECT * FROM inventory_finance WHERE student_id = $needyId AND category = 'Donation'");
        $this->assertEquals(75.00, (float)$finance['amount']);
    }

    public function testCompleteSchoolSummary(): void
    {
        $sid = $this->school['school_id'];

        // Verify the full school exists
        $students = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM students");
        $staff = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM staff WHERE current_school_id = $sid");
        $courses = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM courses WHERE school_id = $sid");

        $this->assertEquals(66, (int)$students['cnt']);
        $this->assertEquals(3, (int)$staff['cnt']); // 2 teachers + 1 admin
        $this->assertEquals(2, (int)$courses['cnt']);

        // Verify gender distribution
        $genders = $this->db->query("SELECT gender, COUNT(*) AS cnt FROM students GROUP BY gender");
        $this->assertCount(2, $genders);
    }

    public function testI18nTranslationStorage(): void
    {
        $this->db->exec("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_donations', 'en', 'Donations')");
        $this->db->exec("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_donations', 'ar', 'التبرعات')");
        $this->db->exec("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_donations', 'he', 'תרומות')");

        $translations = $this->db->query("SELECT * FROM i18n WHERE translation_key = '_donations' ORDER BY lang");
        $this->assertCount(3, $translations);
        $this->assertEquals('التبرعات', $translations[0]['translation']); // ar
        $this->assertEquals('Donations', $translations[1]['translation']); // en
        $this->assertEquals('תרומות', $translations[2]['translation']); // he
    }
}
