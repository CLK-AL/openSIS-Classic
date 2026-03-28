<?php
/**
 * vCard build/parse functions — used by VCardExport.php and tests.
 */

if (!function_exists('buildVCard')) {

function buildVCard(array $d): string
{
    $v = "BEGIN:VCARD\r\nVERSION:3.0\r\n";
    $last   = vcardEsc($d['last'] ?? '');
    $first  = vcardEsc($d['first'] ?? '');
    $middle = vcardEsc($d['middle'] ?? '');
    $prefix = vcardEsc($d['prefix'] ?? '');
    $suffix = vcardEsc($d['suffix'] ?? '');
    $v .= "N:$last;$first;$middle;$prefix;$suffix\r\n";
    $fn = trim("$prefix $first $middle $last $suffix");
    $fn = preg_replace('/\s+/', ' ', $fn);
    $v .= "FN:" . vcardEsc($fn) . "\r\n";
    if (!empty($d['nickname'])) $v .= "NICKNAME:" . vcardEsc($d['nickname']) . "\r\n";
    if (!empty($d['org']))      $v .= "ORG:" . vcardEsc($d['org']) . "\r\n";
    if (!empty($d['title']))    $v .= "TITLE:" . vcardEsc($d['title']) . "\r\n";
    if (!empty($d['phone']))      $v .= "TEL;TYPE=WORK:" . vcardEsc($d['phone']) . "\r\n";
    if (!empty($d['phone_home'])) $v .= "TEL;TYPE=HOME:" . vcardEsc($d['phone_home']) . "\r\n";
    if (!empty($d['phone_cell'])) $v .= "TEL;TYPE=CELL:" . vcardEsc($d['phone_cell']) . "\r\n";
    if (!empty($d['email']))      $v .= "EMAIL;TYPE=WORK:" . vcardEsc($d['email']) . "\r\n";
    if (!empty($d['email_home'])) $v .= "EMAIL;TYPE=HOME:" . vcardEsc($d['email_home']) . "\r\n";
    $street = $d['street'] ?? ''; $city = $d['city'] ?? ''; $state = $d['state'] ?? ''; $zip = $d['zip'] ?? '';
    if ($street || $city || $state || $zip)
        $v .= "ADR;TYPE=HOME:;;" . vcardEsc($street) . ";" . vcardEsc($city) . ";" . vcardEsc($state) . ";" . vcardEsc($zip) . ";\r\n";
    if (!empty($d['bday']) && $d['bday'] !== '0000-00-00') {
        $bday = str_replace('-', '', substr($d['bday'], 0, 10));
        if (strlen($bday) === 8) $v .= "BDAY:$bday\r\n";
    }
    if (!empty($d['gender'])) {
        $g = strtoupper(substr($d['gender'], 0, 1));
        if (in_array($g, ['M', 'F'])) $v .= "X-GENDER:$g\r\n";
    }
    if (!empty($d['note']))       $v .= "NOTE:" . vcardEsc($d['note']) . "\r\n";
    if (!empty($d['categories'])) $v .= "CATEGORIES:" . vcardEsc($d['categories']) . "\r\n";
    $v .= "REV:" . gmdate('Ymd\THis\Z') . "\r\n";
    $v .= "END:VCARD\r\n";
    return $v;
}

function parseVCards(string $data): array
{
    $cards = [];
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
            $colonPos = strpos($line, ':');
            if ($colonPos === false) continue;
            $prop = strtoupper(substr($line, 0, $colonPos));
            $value = substr($line, $colonPos + 1);
            $value = vcardUnesc($value);
            $params = explode(';', $prop);
            $propName = $params[0];
            $types = [];
            foreach ($params as $p) {
                if (stripos($p, 'TYPE=') === 0) $types[] = strtoupper(substr($p, 5));
                elseif (in_array(strtoupper($p), ['HOME','WORK','CELL','VOICE','FAX','PREF','MOBILE'])) $types[] = strtoupper($p);
            }
            switch ($propName) {
                case 'N':
                    $parts = explode(';', $value);
                    $card['last']=$parts[0]??''; $card['first']=$parts[1]??'';
                    $card['middle']=$parts[2]??''; $card['prefix']=$parts[3]??''; $card['suffix']=$parts[4]??'';
                    break;
                case 'TEL':
                    if (in_array('CELL', $types) || in_array('MOBILE', $types)) $card['phone_cell'] = $card['phone_cell'] ?: $value;
                    elseif (in_array('HOME', $types)) $card['phone_home'] = $card['phone_home'] ?: $value;
                    elseif (in_array('WORK', $types)) $card['phone_work'] = $card['phone_work'] ?: $value;
                    else $card['phone_home'] = $card['phone_home'] ?: $value;
                    break;
                case 'EMAIL': $card['email'] = $card['email'] ?: $value; break;
                case 'ADR':
                    $parts = explode(';', $value);
                    $card['street'] = trim(($parts[2]??'').' '.($parts[1]??''));
                    $card['city']=$parts[3]??''; $card['state']=$parts[4]??''; $card['zip']=$parts[5]??'';
                    break;
                case 'BDAY':
                    $bday = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($bday) === 8) $card['bday'] = substr($bday,0,4).'-'.substr($bday,4,2).'-'.substr($bday,6,2);
                    break;
                case 'ORG': $card['org'] = $value; break;
                case 'NOTE': $card['note'] = $value; break;
            }
        }
        $cards[] = $card;
    }
    return $cards;
}

function vcardEsc(string $s): string
{
    return str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $s);
}

function vcardUnesc(string $s): string
{
    return trim(str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $s));
}

} // end function_exists guard
