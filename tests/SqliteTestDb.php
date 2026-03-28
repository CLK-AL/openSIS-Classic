<?php
/**
 * SQLite test database helper.
 *
 * Creates an in-memory SQLite database with the openSIS schema
 * and provides real DBQuery/db_fetch_row/DBGet for integration tests.
 * Replaces TsvMock for tests that need actual SQL execution.
 */

class SqliteTestDb
{
    private static ?PDO $pdo = null;
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct(string $path = ':memory:')
    {
        self::$pdo = new PDO("sqlite:$path", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdo->exec('PRAGMA journal_mode=WAL');
        self::$pdo->exec('PRAGMA foreign_keys=OFF');
    }

    public function getPdo(): PDO { return self::$pdo; }

    public function exec(string $sql): void
    {
        self::$pdo->exec($sql);
    }

    public function query(string $sql): array
    {
        $stmt = self::$pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryOne(string $sql): ?array
    {
        $rows = $this->query($sql);
        return $rows[0] ?? null;
    }

    public function lastInsertId(): string
    {
        return self::$pdo->lastInsertId();
    }

    /**
     * Create all core openSIS tables needed for the classroom simulation.
     */
    public function createSchema(): void
    {
        // Schools
        $this->exec("CREATE TABLE IF NOT EXISTS schools (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, address TEXT, city TEXT, state TEXT,
            principal TEXT, phone TEXT, email TEXT
        )");

        // School years / marking periods
        $this->exec("CREATE TABLE IF NOT EXISTS school_years (
            marking_period_id INTEGER PRIMARY KEY AUTOINCREMENT,
            syear INTEGER, school_id INTEGER, title TEXT, short_name TEXT,
            sort_order INTEGER, start_date DATE, end_date DATE
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS marking_periods (
            marking_period_id INTEGER PRIMARY KEY AUTOINCREMENT,
            syear INTEGER, school_id INTEGER, mp_type TEXT, title TEXT,
            short_name TEXT, start_date DATE, end_date DATE,
            post_start_date DATE, post_end_date DATE
        )");

        // Staff
        $this->exec("CREATE TABLE IF NOT EXISTS staff (
            staff_id INTEGER PRIMARY KEY AUTOINCREMENT,
            current_school_id INTEGER, first_name TEXT, last_name TEXT,
            middle_name TEXT, phone TEXT, email TEXT, profile TEXT,
            profile_id INTEGER, gender TEXT, is_disable TEXT DEFAULT 'N'
        )");

        // Students
        $this->exec("CREATE TABLE IF NOT EXISTS students (
            student_id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT, last_name TEXT, middle_name TEXT,
            gender TEXT, birthdate DATE, email TEXT, phone TEXT,
            common_name TEXT, alt_id TEXT, is_disable TEXT DEFAULT 'N'
        )");

        // Enrollment
        $this->exec("CREATE TABLE IF NOT EXISTS student_enrollment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER, syear INTEGER, school_id INTEGER,
            grade_id INTEGER, start_date DATE, end_date DATE,
            enrollment_code INTEGER, drop_code INTEGER
        )");

        // Courses
        $this->exec("CREATE TABLE IF NOT EXISTS courses (
            course_id INTEGER PRIMARY KEY AUTOINCREMENT,
            syear INTEGER, school_id INTEGER, title TEXT, subject_id INTEGER
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS course_periods (
            course_period_id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER, teacher_id INTEGER, room TEXT, total_seats INTEGER,
            marking_period_id INTEGER
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS schedule (
            student_id INTEGER, course_period_id INTEGER,
            start_date DATE, end_date DATE, marking_period_id INTEGER
        )");

        // Grades
        $this->exec("CREATE TABLE IF NOT EXISTS gradebook_assignments (
            assignment_id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_period_id INTEGER, title TEXT, assigned_date DATE,
            due_date DATE, points REAL
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS gradebook_grades (
            student_id INTEGER, assignment_id INTEGER,
            points REAL, comment TEXT
        )");

        // Attendance
        $this->exec("CREATE TABLE IF NOT EXISTS attendance_period (
            student_id INTEGER, course_period_id INTEGER,
            school_date DATE, attendance_code TEXT, remarks TEXT
        )");

        // Library
        $this->exec("CREATE TABLE IF NOT EXISTS library_book_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            title TEXT, sort_order INTEGER DEFAULT 0
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS library_books (
            book_id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            title TEXT, author TEXT, isbn TEXT, category_id INTEGER,
            total_copies INTEGER DEFAULT 1, available_copies INTEGER DEFAULT 1,
            location TEXT, is_active TEXT DEFAULT 'Y'
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS library_checkout (
            id INTEGER PRIMARY KEY AUTOINCREMENT, book_id INTEGER,
            school_id INTEGER, borrower_type TEXT, borrower_id INTEGER,
            checkout_date DATE, due_date DATE, return_date DATE,
            status TEXT DEFAULT 'checked_out'
        )");

        // Inventory
        $this->exec("CREATE TABLE IF NOT EXISTS inventory_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            title TEXT, sort_order INTEGER DEFAULT 0
        )");

        $this->exec("CREATE TABLE IF NOT EXISTS inventory_equipment (
            equipment_id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            name TEXT, serial_number TEXT, category_id INTEGER,
            condition_status TEXT DEFAULT 'Good', purchase_cost REAL,
            total_quantity INTEGER DEFAULT 1, available_quantity INTEGER DEFAULT 1,
            is_active TEXT DEFAULT 'Y'
        )");

        // Donations
        $this->exec("CREATE TABLE IF NOT EXISTS inventory_donations (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            donation_type TEXT, donor_name TEXT, donor_type TEXT,
            title TEXT, description TEXT, monetary_value REAL DEFAULT 0,
            quantity INTEGER DEFAULT 1, donation_date DATE,
            beneficiary_student_id INTEGER, beneficiary_tag TEXT,
            status TEXT DEFAULT 'received'
        )");

        // Student Needs
        $this->exec("CREATE TABLE IF NOT EXISTS inventory_student_needs (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            student_id INTEGER, need_type TEXT, priority TEXT DEFAULT 'medium',
            description TEXT, estimated_cost REAL DEFAULT 0,
            funded_amount REAL DEFAULT 0, funded_by_donation_id INTEGER,
            status TEXT DEFAULT 'open', date_identified DATE, date_fulfilled DATE
        )");

        // Finance
        $this->exec("CREATE TABLE IF NOT EXISTS inventory_finance (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            category TEXT, title TEXT, description TEXT,
            student_id INTEGER, amount REAL, payment_type TEXT,
            payment_date DATE, status TEXT DEFAULT 'paid',
            reference_type TEXT, receipt_number TEXT
        )");

        // Field trips
        $this->exec("CREATE TABLE IF NOT EXISTS inventory_field_trips (
            id INTEGER PRIMARY KEY AUTOINCREMENT, school_id INTEGER,
            title TEXT, destination TEXT, trip_date DATE, return_date DATE,
            cost_per_student REAL DEFAULT 0, total_budget REAL DEFAULT 0,
            collected_amount REAL DEFAULT 0, status TEXT DEFAULT 'planned'
        )");

        // i18n
        $this->exec("CREATE TABLE IF NOT EXISTS i18n (
            translation_key TEXT, lang TEXT, translation TEXT,
            PRIMARY KEY (translation_key, lang)
        )");
    }

    /**
     * Seed a complete school with students, staff, courses for testing.
     */
    public function seedSchool(string $name, int $syear, int $numClasses, int $studentsPerClass): array
    {
        // Create school
        $this->exec("INSERT INTO schools (title, city, state, principal)
                      VALUES ('$name', 'Springfield', 'IL', 'Dr. Principal')");
        $schoolId = (int)$this->lastInsertId();

        // School year
        $this->exec("INSERT INTO school_years (syear, school_id, title, short_name, sort_order, start_date, end_date)
                      VALUES ($syear, $schoolId, 'Full Year', 'FY', 1, '$syear-08-15', '" . ($syear+1) . "-06-15')");

        // Staff (teachers)
        $teachers = [];
        $teacherNames = [
            ['Sarah', 'Johnson'], ['Michael', 'Chen'], ['Emily', 'Williams'],
            ['David', 'Brown'], ['Rachel', 'Garcia'], ['James', 'Taylor'],
        ];
        foreach (array_slice($teacherNames, 0, $numClasses) as $i => $tn) {
            $this->exec("INSERT INTO staff (current_school_id, first_name, last_name, profile, profile_id, email, gender)
                          VALUES ($schoolId, '{$tn[0]}', '{$tn[1]}', 'teacher', 2, '" . strtolower($tn[0]) . "@school.edu', 'Female')");
            $teachers[] = (int)$this->lastInsertId();
        }

        // Admin
        $this->exec("INSERT INTO staff (current_school_id, first_name, last_name, profile, profile_id, email)
                      VALUES ($schoolId, 'Admin', 'User', 'admin', 1, 'admin@school.edu')");
        $adminId = (int)$this->lastInsertId();

        // Students + enrollment
        $studentIds = [];
        $firstNames = ['Alice','Bob','Charlie','Diana','Eve','Frank','Grace','Henry','Iris','Jack',
                        'Kate','Liam','Mia','Noah','Olivia','Peter','Quinn','Rose','Sam','Tara',
                        'Uma','Victor','Wendy','Xander','Yara','Zack','Amy','Ben','Clara','Dan',
                        'Ella','Finn','Gina'];
        $lastNames = ['Smith','Jones','Brown','Wilson','Taylor','Davis','Clark','Lewis','Walker','Hall',
                       'Allen','Young','King','Wright','Green','Baker','Adams','Nelson','Hill','Moore',
                       'White','Harris','Martin','Lee','Garcia','Lopez','Gonzalez','Hernandez','Rivera','Campbell',
                       'Mitchell','Roberts','Carter'];

        for ($c = 0; $c < $numClasses; $c++) {
            for ($s = 0; $s < $studentsPerClass; $s++) {
                $idx = ($c * $studentsPerClass + $s) % count($firstNames);
                $fn = $firstNames[$idx];
                $ln = $lastNames[$idx];
                $grade = $c + 1;
                $bday = (2010 - $c) . '-' . sprintf('%02d', ($s % 12) + 1) . '-' . sprintf('%02d', ($s % 28) + 1);
                $gender = ($s % 2 === 0) ? 'Female' : 'Male';

                $this->exec("INSERT INTO students (first_name, last_name, gender, birthdate)
                              VALUES ('$fn', '$ln', '$gender', '$bday')");
                $sid = (int)$this->lastInsertId();
                $studentIds[] = $sid;

                $this->exec("INSERT INTO student_enrollment (student_id, syear, school_id, grade_id, start_date)
                              VALUES ($sid, $syear, $schoolId, $grade, '$syear-08-15')");
            }
        }

        // Courses + periods + scheduling
        $courseNames = ['Mathematics', 'Science', 'English Literature', 'History', 'Art', 'Physical Education'];
        $courseIds = [];
        for ($c = 0; $c < $numClasses; $c++) {
            $courseName = $courseNames[$c % count($courseNames)] . ' - Class ' . ($c + 1);
            $this->exec("INSERT INTO courses (syear, school_id, title)
                          VALUES ($syear, $schoolId, '$courseName')");
            $courseId = (int)$this->lastInsertId();
            $courseIds[] = $courseId;

            $this->exec("INSERT INTO course_periods (course_id, teacher_id, room, total_seats)
                          VALUES ($courseId, {$teachers[$c]}, 'Room " . ($c+101) . "', $studentsPerClass)");
            $cpId = (int)$this->lastInsertId();

            // Schedule students
            $start = $c * $studentsPerClass;
            for ($s = $start; $s < $start + $studentsPerClass; $s++) {
                $this->exec("INSERT INTO schedule (student_id, course_period_id, start_date)
                              VALUES ({$studentIds[$s]}, $cpId, '$syear-08-15')");
            }
        }

        return [
            'school_id' => $schoolId,
            'admin_id' => $adminId,
            'teacher_ids' => $teachers,
            'student_ids' => $studentIds,
            'course_ids' => $courseIds,
        ];
    }
}
