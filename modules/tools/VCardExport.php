<?php
#***************************************************************************************
#  vCard Export — Export students, staff, or parents as vCard (.vcf) files
#***************************************************************************************

include('../../RedirectModulesInc.php');
require_once 'functions/VCardFnc.php';

global $connection;

$exportType = optional_param('export_type', '', PARAM_ALPHA);
$exportFormat = optional_param('format', 'vcf', PARAM_ALPHA);

// ── Export action ────────────────────────────────────────────────────
if ($exportType && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $vcards = '';
    $filename = 'opensis_' . $exportType . '_' . date('Y-m-d') . '.vcf';

    switch ($exportType) {
        case 'students':
            $vcards = exportStudents();
            break;
        case 'staff':
            $vcards = exportStaff();
            break;
        case 'parents':
            $vcards = exportParents();
            break;
    }

    if ($vcards) {
        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($vcards));
        echo $vcards;
        exit;
    }
}

// ── Export functions ─────────────────────────────────────────────────
function exportStudents(): string
{
    $syear = UserSyear();
    $school = UserSchool();
    $students = DBGet(DBQuery("
        SELECT s.student_id, s.first_name, s.last_name, s.middle_name, s.name_suffix,
               s.email, s.phone, s.gender, s.birthdate, s.common_name,
               sa.street_address_1, sa.street_address_2, sa.city, sa.state, sa.zipcode
        FROM students s
        LEFT JOIN student_address sa ON s.student_id = sa.student_id AND sa.type = 'home'
        JOIN student_enrollment se ON s.student_id = se.student_id
        WHERE se.syear = '$syear' AND se.school_id = '$school'
        AND (se.end_date IS NULL OR se.end_date = '0000-00-00' OR se.end_date >= '" . date('Y-m-d') . "')
        GROUP BY s.student_id
        ORDER BY s.last_name, s.first_name
    "));

    $vcf = '';
    foreach ($students as $s) {
        $vcf .= buildVCard([
            'first'   => $s['FIRST_NAME'],
            'last'    => $s['LAST_NAME'],
            'middle'  => $s['MIDDLE_NAME'],
            'suffix'  => $s['NAME_SUFFIX'],
            'email'   => $s['EMAIL'],
            'phone'   => $s['PHONE'],
            'gender'  => $s['GENDER'],
            'bday'    => $s['BIRTHDATE'],
            'nickname'=> $s['COMMON_NAME'],
            'street'  => trim(($s['STREET_ADDRESS_1'] ?? '') . ' ' . ($s['STREET_ADDRESS_2'] ?? '')),
            'city'    => $s['CITY'] ?? '',
            'state'   => $s['STATE'] ?? '',
            'zip'     => $s['ZIPCODE'] ?? '',
            'org'     => 'Student',
            'note'    => 'Student ID: ' . $s['STUDENT_ID'],
            'categories' => 'Student',
        ]);
    }
    return $vcf;
}

function exportStaff(): string
{
    $school = UserSchool();
    $staff = DBGet(DBQuery("
        SELECT s.staff_id, s.first_name, s.last_name, s.middle_name, s.title,
               s.phone, s.email, s.gender, s.birthdate, s.profile, s.name_suffix,
               sc.staff_home_phone, sc.staff_mobile_phone, sc.staff_work_phone,
               sc.staff_work_email, sc.staff_personal_email,
               sa.staff_address1_primary, sa.staff_address2_primary,
               sa.staff_city_primary, sa.staff_state_primary, sa.staff_zip_primary
        FROM staff s
        LEFT JOIN staff_contact sc ON s.staff_id = sc.staff_id
        LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
        WHERE s.current_school_id = '$school'
        ORDER BY s.last_name, s.first_name
    "));

    $vcf = '';
    foreach ($staff as $s) {
        $vcf .= buildVCard([
            'prefix'    => $s['TITLE'],
            'first'     => $s['FIRST_NAME'],
            'last'      => $s['LAST_NAME'],
            'middle'    => $s['MIDDLE_NAME'],
            'suffix'    => $s['NAME_SUFFIX'],
            'email'     => $s['STAFF_WORK_EMAIL'] ?: $s['EMAIL'],
            'email_home'=> $s['STAFF_PERSONAL_EMAIL'],
            'phone'     => $s['STAFF_WORK_PHONE'] ?: $s['PHONE'],
            'phone_home'=> $s['STAFF_HOME_PHONE'],
            'phone_cell'=> $s['STAFF_MOBILE_PHONE'],
            'gender'    => $s['GENDER'],
            'bday'      => $s['BIRTHDATE'],
            'street'    => trim(($s['STAFF_ADDRESS1_PRIMARY'] ?? '') . ' ' . ($s['STAFF_ADDRESS2_PRIMARY'] ?? '')),
            'city'      => $s['STAFF_CITY_PRIMARY'] ?? '',
            'state'     => $s['STAFF_STATE_PRIMARY'] ?? '',
            'zip'       => $s['STAFF_ZIP_PRIMARY'] ?? '',
            'org'       => ucfirst($s['PROFILE'] ?? 'Staff'),
            'title'     => ucfirst($s['PROFILE'] ?? ''),
            'note'      => 'Staff ID: ' . $s['STAFF_ID'],
            'categories'=> 'Staff',
        ]);
    }
    return $vcf;
}

function exportParents(): string
{
    $syear = UserSyear();
    $school = UserSchool();
    $parents = DBGet(DBQuery("
        SELECT DISTINCT p.staff_id, p.first_name, p.last_name, p.middle_name, p.title,
               p.home_phone, p.work_phone, p.cell_phone, p.email,
               sa.street_address_1, sa.street_address_2, sa.city, sa.state, sa.zipcode,
               sjp.relationship
        FROM people p
        JOIN students_join_people sjp ON p.staff_id = sjp.person_id
        JOIN student_enrollment se ON sjp.student_id = se.student_id
        LEFT JOIN student_address sa ON sjp.student_id = sa.student_id AND sa.type = 'home'
        WHERE se.syear = '$syear' AND se.school_id = '$school'
        AND (se.end_date IS NULL OR se.end_date = '0000-00-00' OR se.end_date >= '" . date('Y-m-d') . "')
        ORDER BY p.last_name, p.first_name
    "));

    $vcf = '';
    foreach ($parents as $p) {
        $vcf .= buildVCard([
            'prefix'    => $p['TITLE'],
            'first'     => $p['FIRST_NAME'],
            'last'      => $p['LAST_NAME'],
            'middle'    => $p['MIDDLE_NAME'],
            'email'     => $p['EMAIL'],
            'phone_home'=> $p['HOME_PHONE'],
            'phone'     => $p['WORK_PHONE'],
            'phone_cell'=> $p['CELL_PHONE'],
            'street'    => trim(($p['STREET_ADDRESS_1'] ?? '') . ' ' . ($p['STREET_ADDRESS_2'] ?? '')),
            'city'      => $p['CITY'] ?? '',
            'state'     => $p['STATE'] ?? '',
            'zip'       => $p['ZIPCODE'] ?? '',
            'org'       => $p['RELATIONSHIP'] ?? 'Parent',
            'categories'=> 'Parent',
        ]);
    }
    return $vcf;
}

function buildVCard(array $d): string
{
    $v = "BEGIN:VCARD\r\nVERSION:3.0\r\n";

    // Name
    $last   = esc($d['last'] ?? '');
    $first  = esc($d['first'] ?? '');
    $middle = esc($d['middle'] ?? '');
    $prefix = esc($d['prefix'] ?? '');
    $suffix = esc($d['suffix'] ?? '');
    $v .= "N:$last;$first;$middle;$prefix;$suffix\r\n";
    $fn = trim("$prefix $first $middle $last $suffix");
    $fn = preg_replace('/\s+/', ' ', $fn);
    $v .= "FN:" . esc($fn) . "\r\n";

    if (!empty($d['nickname'])) $v .= "NICKNAME:" . esc($d['nickname']) . "\r\n";
    if (!empty($d['org']))      $v .= "ORG:" . esc($d['org']) . "\r\n";
    if (!empty($d['title']))    $v .= "TITLE:" . esc($d['title']) . "\r\n";

    // Phones
    if (!empty($d['phone']))      $v .= "TEL;TYPE=WORK:" . esc($d['phone']) . "\r\n";
    if (!empty($d['phone_home'])) $v .= "TEL;TYPE=HOME:" . esc($d['phone_home']) . "\r\n";
    if (!empty($d['phone_cell'])) $v .= "TEL;TYPE=CELL:" . esc($d['phone_cell']) . "\r\n";

    // Emails
    if (!empty($d['email']))      $v .= "EMAIL;TYPE=WORK:" . esc($d['email']) . "\r\n";
    if (!empty($d['email_home'])) $v .= "EMAIL;TYPE=HOME:" . esc($d['email_home']) . "\r\n";

    // Address
    $street = $d['street'] ?? '';
    $city   = $d['city'] ?? '';
    $state  = $d['state'] ?? '';
    $zip    = $d['zip'] ?? '';
    if ($street || $city || $state || $zip) {
        $v .= "ADR;TYPE=HOME:;;" . esc($street) . ";" . esc($city) . ";" . esc($state) . ";" . esc($zip) . ";\r\n";
    }

    // Birthday
    if (!empty($d['bday']) && $d['bday'] !== '0000-00-00') {
        $bday = str_replace('-', '', substr($d['bday'], 0, 10));
        if (strlen($bday) === 8) $v .= "BDAY:$bday\r\n";
    }

    // Gender (vCard 4.0 but widely supported)
    if (!empty($d['gender'])) {
        $g = strtoupper(substr($d['gender'], 0, 1));
        if (in_array($g, ['M', 'F'])) $v .= "X-GENDER:$g\r\n";
    }

    if (!empty($d['note']))       $v .= "NOTE:" . esc($d['note']) . "\r\n";
    if (!empty($d['categories'])) $v .= "CATEGORIES:" . esc($d['categories']) . "\r\n";

    $v .= "REV:" . gmdate('Ymd\THis\Z') . "\r\n";
    $v .= "END:VCARD\r\n";
    return $v;
}

function esc(string $s): string
{
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace("\n", '\\n', $s);
    $s = str_replace(',', '\\,', $s);
    $s = str_replace(';', '\\;', $s);
    return $s;
}

// ── UI ───────────────────────────────────────────────────────────────
$CSRF_TOKEN = CSRFSecure::CreateToken();

PopTable('header', 'vCard Export');
?>
<p>Export contacts as vCard (.vcf) files compatible with Outlook, Apple Contacts, Google Contacts, and mobile phones.</p>

<div class="row" style="margin-top:15px">
    <?php foreach (['students' => ['Students', 'icon-man-woman', 'Active students with addresses'], 'staff' => ['Staff', 'icon-users', 'All staff with contact details'], 'parents' => ['Parents', 'icon-home4', 'Parents of active students']] as $type => $info): ?>
    <div class="col-md-4" style="margin-bottom:15px">
        <div class="panel panel-body text-center" style="padding:20px">
            <i class="<?php echo $info[1]; ?>" style="font-size:40px;color:#666;display:block;margin-bottom:10px"></i>
            <h5><?php echo $info[0]; ?></h5>
            <p class="text-muted"><?php echo $info[2]; ?></p>
            <form method="POST">
                <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
                <input type="hidden" name="export_type" value="<?php echo $type; ?>">
                <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
                <?php $CSRF_TOKEN = CSRFSecure::CreateToken(); ?>
                <button type="submit" class="btn btn-primary">
                    <i class="icon-download"></i> Export .vcf
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php PopTable('footer'); ?>

<?php
// ═════════════════════════════════════════════════════════════════════
// vCard Import Section
// ═════════════════════════════════════════════════════════════════════

PopTable('header', 'vCard Import');
?>
<p>Import contacts from a vCard (.vcf) file. Each vCard will be matched or created as a parent/guardian contact.</p>

<?php
$importResult = '';
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'import' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    if (isset($_FILES['vcf_file']) && $_FILES['vcf_file']['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($_FILES['vcf_file']['tmp_name']);
        $cards = parseVCards($content);
        $imported = 0;
        $skipped = 0;

        foreach ($cards as $card) {
            if (empty($card['first']) && empty($card['last'])) {
                $skipped++;
                continue;
            }

            $firstName = mysqli_real_escape_string($connection, $card['first'] ?? '');
            $lastName  = mysqli_real_escape_string($connection, $card['last'] ?? '');
            $email     = mysqli_real_escape_string($connection, $card['email'] ?? '');
            $homePhone = mysqli_real_escape_string($connection, $card['phone_home'] ?? '');
            $workPhone = mysqli_real_escape_string($connection, $card['phone_work'] ?? '');
            $cellPhone = mysqli_real_escape_string($connection, $card['phone_cell'] ?? '');

            // Check for duplicate
            $existing = DBGet(DBQuery("SELECT staff_id FROM people WHERE first_name='$firstName' AND last_name='$lastName' AND email='$email' LIMIT 1"));
            if (!empty($existing)) {
                $skipped++;
                continue;
            }

            $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);
            $schoolId = UserSchool();

            DBQuery("INSERT INTO people (current_school_id, first_name, last_name, home_phone, work_phone, cell_phone, email, profile, profile_id, is_disable, last_updated, updated_by)
                     VALUES ('$schoolId', '$firstName', '$lastName', '$homePhone', '$workPhone', '$cellPhone', '$email', 'parent', 4, 'N', NOW(), '$staffId')");
            $imported++;
        }

        echo '<div class="alert alert-success">';
        echo "Imported: <strong>$imported</strong> contacts. Skipped: <strong>$skipped</strong> (duplicates or empty names).";
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">Please select a .vcf file to upload.</div>';
    }
}

$CSRF_TOKEN = CSRFSecure::CreateToken();
?>

<form method="POST" enctype="multipart/form-data" style="margin-top:10px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="import">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
    <div class="form-group">
        <label>Select vCard file (.vcf)</label>
        <input type="file" name="vcf_file" accept=".vcf,text/vcard" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-info">
        <i class="icon-upload"></i> Import Contacts
    </button>
</form>

<?php
PopTable('footer');

// ── vCard Parser ─────────────────────────────────────────────────────
function parseVCards(string $data): array
{
    $cards = [];
    // Unfold continued lines (RFC 6350 Section 3.2)
    $data = preg_replace('/\r?\n[ \t]/', '', $data);
    $blocks = preg_split('/(?=BEGIN:VCARD)/i', $data);

    foreach ($blocks as $block) {
        $block = trim($block);
        if (stripos($block, 'BEGIN:VCARD') === false) continue;

        $card = ['first'=>'','last'=>'','middle'=>'','prefix'=>'','suffix'=>'',
                 'email'=>'','phone_home'=>'','phone_work'=>'','phone_cell'=>'',
                 'street'=>'','city'=>'','state'=>'','zip'=>'','bday'=>'','org'=>'','note'=>''];

        $lines = preg_split('/\r?\n/', $block);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || $line === 'BEGIN:VCARD' || $line === 'END:VCARD') continue;

            // Split property:value
            $colonPos = strpos($line, ':');
            if ($colonPos === false) continue;
            $prop  = strtoupper(substr($line, 0, $colonPos));
            $value = substr($line, $colonPos + 1);
            $value = unesc($value);

            // Handle property parameters (e.g., TEL;TYPE=HOME)
            $params = explode(';', $prop);
            $propName = $params[0];
            $types = [];
            foreach ($params as $p) {
                if (stripos($p, 'TYPE=') === 0) {
                    $types[] = strtoupper(substr($p, 5));
                } elseif (in_array(strtoupper($p), ['HOME','WORK','CELL','VOICE','FAX','PREF'])) {
                    $types[] = strtoupper($p);
                }
            }

            switch ($propName) {
                case 'N':
                    $parts = explode(';', $value);
                    $card['last']   = $parts[0] ?? '';
                    $card['first']  = $parts[1] ?? '';
                    $card['middle'] = $parts[2] ?? '';
                    $card['prefix'] = $parts[3] ?? '';
                    $card['suffix'] = $parts[4] ?? '';
                    break;
                case 'TEL':
                    if (in_array('CELL', $types) || in_array('MOBILE', $types))
                        $card['phone_cell'] = $card['phone_cell'] ?: $value;
                    elseif (in_array('HOME', $types))
                        $card['phone_home'] = $card['phone_home'] ?: $value;
                    elseif (in_array('WORK', $types))
                        $card['phone_work'] = $card['phone_work'] ?: $value;
                    else
                        $card['phone_home'] = $card['phone_home'] ?: $value;
                    break;
                case 'EMAIL':
                    $card['email'] = $card['email'] ?: $value;
                    break;
                case 'ADR':
                    $parts = explode(';', $value);
                    // ADR format: PO;Extended;Street;City;Region;PostalCode;Country
                    $card['street'] = trim(($parts[2] ?? '') . ' ' . ($parts[1] ?? ''));
                    $card['city']   = $parts[3] ?? '';
                    $card['state']  = $parts[4] ?? '';
                    $card['zip']    = $parts[5] ?? '';
                    break;
                case 'BDAY':
                    $bday = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($bday) === 8) {
                        $card['bday'] = substr($bday,0,4).'-'.substr($bday,4,2).'-'.substr($bday,6,2);
                    }
                    break;
                case 'ORG':
                    $card['org'] = $value;
                    break;
                case 'NOTE':
                    $card['note'] = $value;
                    break;
            }
        }
        $cards[] = $card;
    }
    return $cards;
}

function unesc(string $s): string
{
    $s = str_replace('\\n', "\n", $s);
    $s = str_replace('\\,', ',', $s);
    $s = str_replace('\\;', ';', $s);
    $s = str_replace('\\\\', '\\', $s);
    return trim($s);
}
?>
