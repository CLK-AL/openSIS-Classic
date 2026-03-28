<?php
#**************************************************************************
#  Translation Manager - i18n management UI for openSIS
#  Manages translation keys across all supported languages using a
#  database-backed i18n table with compound indices (key, lang).
#***************************************************************************************

include('../../RedirectModulesInc.php');

global $connection;
include 'lang/supportedLanguages.php';

// ── Ensure i18n table exists ──────────────────────────────────────────
$tableCheck = DBQuery("SHOW TABLES LIKE 'i18n'");
if ($tableCheck && db_fetch_row($tableCheck) === null) {
    DBQuery("CREATE TABLE `i18n` (
        `translation_key` VARCHAR(255) NOT NULL,
        `lang` VARCHAR(10) NOT NULL,
        `translation` TEXT NOT NULL DEFAULT '',
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL,
        PRIMARY KEY (`translation_key`, `lang`),
        INDEX `idx_lang` (`lang`),
        INDEX `idx_key` (`translation_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// ── Import from lang files into DB ────────────────────────────────────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'import') {
    if (CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
        $imported = 0;
        foreach ($supportedLanguages as $langCode => $langInfo) {
            $langFile = "lang/lang_{$langCode}.php";
            if (!file_exists($langFile)) continue;

            $content = file_get_contents($langFile);
            preg_match_all('/define\([\'"]([^\'"]+)[\'"],\s*[\'"](.*)[\'"]\);/', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $key = mysqli_real_escape_string($connection, $match[1]);
                $val = mysqli_real_escape_string($connection, $match[2]);
                $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

                DBQuery("INSERT INTO i18n (translation_key, lang, translation, updated_by)
                         VALUES ('$key', '$langCode', '$val', $staffId)
                         ON DUPLICATE KEY UPDATE translation = VALUES(translation), updated_by = VALUES(updated_by)");
                $imported++;
            }
        }
        echo '<div class="alert alert-success">' . $imported . ' translations imported/updated from language files.</div>';
    }
}

// ── Export to lang files ──────────────────────────────────────────────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'export') {
    if (CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
        $exported = 0;
        foreach ($supportedLanguages as $langCode => $langInfo) {
            $rows = DBGet(DBQuery("SELECT translation_key, translation FROM i18n WHERE lang='$langCode' ORDER BY translation_key"));
            if (empty($rows)) continue;

            $php = "<?php\n";
            foreach ($rows as $row) {
                $key = $row['translation_key'];
                $val = str_replace('"', '\\"', $row['translation']);
                $php .= "define(\"$key\", \"$val\");\n";
            }
            $php .= "?>";
            file_put_contents("lang/lang_{$langCode}.php", $php);
            $exported++;
        }
        echo '<div class="alert alert-success">' . $exported . ' language files exported.</div>';
    }
}

// ── Save translations (form POST - saves all langs for one key) ──────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'save') {
    if (CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
        $saveKey = optional_param('save_key', '', PARAM_RAW);
        $savedLangs = [];

        if ($saveKey) {
            $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);
            foreach ($supportedLanguages as $lc => $li) {
                $paramName = 'trans_' . $lc;
                if (isset($_POST[$paramName])) {
                    $key = mysqli_real_escape_string($connection, $saveKey);
                    $lang = mysqli_real_escape_string($connection, $lc);
                    $val = mysqli_real_escape_string($connection, $_POST[$paramName]);

                    DBQuery("INSERT INTO i18n (translation_key, lang, translation, updated_by)
                             VALUES ('$key', '$lang', '$val', $staffId)
                             ON DUPLICATE KEY UPDATE translation = VALUES(translation), updated_by = VALUES(updated_by)");
                    $savedLangs[] = $lc;
                }
            }
            if (!empty($savedLangs)) {
                echo '<div class="alert alert-success">Saved <strong>' . htmlspecialchars($saveKey) . '</strong> for: ' . implode(', ', $savedLangs) . '</div>';
            }
        }
    }
}

// ── Add new key ──────────────────────────────────────────────────────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'addkey') {
    if (CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
        $newKey = optional_param('new_key', '', PARAM_RAW);
        if ($newKey) {
            $key = mysqli_real_escape_string($connection, $newKey);
            $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);
            foreach ($supportedLanguages as $langCode => $langInfo) {
                DBQuery("INSERT IGNORE INTO i18n (translation_key, lang, translation, updated_by)
                         VALUES ('$key', '$langCode', '', $staffId)");
            }
            echo '<div class="alert alert-success">Key <strong>' . htmlspecialchars($newKey) . '</strong> added for all languages.</div>';
        }
    }
}

// ── Delete key ───────────────────────────────────────────────────────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'deletekey') {
    if (CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
        $delKey = optional_param('del_key', '', PARAM_RAW);
        if ($delKey) {
            $key = mysqli_real_escape_string($connection, $delKey);
            DBQuery("DELETE FROM i18n WHERE translation_key='$key'");
            echo '<div class="alert alert-warning">Key <strong>' . htmlspecialchars($delKey) . '</strong> deleted from all languages.</div>';
        }
    }
}

// ── UI Parameters ────────────────────────────────────────────────────
$filterLang = optional_param('filter_lang', '', PARAM_RAW);
$filterSearch = optional_param('filter_search', '', PARAM_RAW);
$filterEmpty = optional_param('filter_empty', '', PARAM_RAW);
$page = max(1, optional_param('page', 1, PARAM_INT));
$perPage = max(25, min(100, optional_param('per_page', 50, PARAM_INT)));

// ── Count total keys ─────────────────────────────────────────────────
$countResult = DBGet(DBQuery("SELECT COUNT(DISTINCT translation_key) AS cnt FROM i18n"));
$totalKeys = $countResult ? (int)$countResult[1]['cnt'] : 0;

// ── Build filtered key list ──────────────────────────────────────────
$where = "1=1";
if ($filterSearch) {
    $s = mysqli_real_escape_string($connection, $filterSearch);
    $where .= " AND (i.translation_key LIKE '%$s%' OR i.translation LIKE '%$s%')";
}
if ($filterEmpty && $filterLang) {
    $fl = mysqli_real_escape_string($connection, $filterLang);
    $where .= " AND i.lang='$fl' AND (i.translation = '' OR i.translation IS NULL)";
} elseif ($filterLang) {
    $fl = mysqli_real_escape_string($connection, $filterLang);
    $where .= " AND i.lang='$fl'";
}

// Get distinct keys matching filter
$filteredCount = DBGet(DBQuery("SELECT COUNT(DISTINCT i.translation_key) AS cnt FROM i18n i WHERE $where"));
$totalFiltered = $filteredCount ? (int)$filteredCount[1]['cnt'] : 0;
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$keyRows = DBGet(DBQuery("SELECT DISTINCT i.translation_key FROM i18n i WHERE $where ORDER BY i.translation_key LIMIT $offset, $perPage"));

// ── Determine display languages ──────────────────────────────────────
$displayLangs = [];
if ($filterLang && isset($supportedLanguages[$filterLang])) {
    // Always show English + selected language
    $displayLangs['en'] = $supportedLanguages['en'];
    if ($filterLang !== 'en') {
        $displayLangs[$filterLang] = $supportedLanguages[$filterLang];
    }
} else {
    $displayLangs = $supportedLanguages;
}

// ── Fetch translations for current page keys ─────────────────────────
$translations = [];
if (!empty($keyRows)) {
    $keyList = [];
    foreach ($keyRows as $r) {
        $keyList[] = "'" . mysqli_real_escape_string($connection, $r['translation_key']) . "'";
    }
    $inClause = implode(',', $keyList);
    $allTrans = DBGet(DBQuery("SELECT translation_key, lang, translation FROM i18n WHERE translation_key IN ($inClause)"));
    if ($allTrans) {
        foreach ($allTrans as $t) {
            $translations[$t['translation_key']][$t['lang']] = $t['translation'];
        }
    }
}

// ── Stats ────────────────────────────────────────────────────────────
$stats = [];
foreach ($supportedLanguages as $lc => $li) {
    $r = DBGet(DBQuery("SELECT COUNT(*) AS total, SUM(CASE WHEN translation != '' THEN 1 ELSE 0 END) AS filled FROM i18n WHERE lang='$lc'"));
    $stats[$lc] = $r ? ['total' => (int)$r[1]['total'], 'filled' => (int)$r[1]['filled']] : ['total' => 0, 'filled' => 0];
}

// ── CSRF Token ───────────────────────────────────────────────────────
$CSRF_TOKEN = CSRFSecure::CreateToken();

// ═══════════════════════════════════════════════════════════════════════
// ── RENDER UI ─────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════

PopTable('header', 'Translation Manager');
?>

<!-- Stats Cards -->
<div class="row" style="margin-bottom:15px">
    <?php foreach ($supportedLanguages as $lc => $li):
        $pct = $stats[$lc]['total'] > 0 ? round(($stats[$lc]['filled'] / $stats[$lc]['total']) * 100) : 0;
    ?>
    <div class="col-md-2" style="margin-bottom:8px">
        <div class="panel panel-body text-center" style="padding:10px;margin:0">
            <h6 style="margin:0 0 5px"><?php echo htmlspecialchars($li['name']); ?> (<?php echo $lc; ?>)</h6>
            <div class="progress" style="margin:5px 0;height:8px">
                <div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <small><?php echo $stats[$lc]['filled']; ?>/<?php echo $stats[$lc]['total']; ?> (<?php echo $pct; ?>%)</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Action Buttons -->
<div style="margin-bottom:15px">
    <form method="POST" style="display:inline">
        <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
        <input type="hidden" name="modfunc" value="import">
        <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Import all translations from lang files into database?')">
            <i class="icon-database-insert"></i> Import from Files
        </button>
    </form>
    <?php $CSRF_TOKEN = CSRFSecure::CreateToken(); ?>
    <form method="POST" style="display:inline">
        <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
        <input type="hidden" name="modfunc" value="export">
        <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Export all translations from database to lang files? This will overwrite existing files.')">
            <i class="icon-database-export"></i> Export to Files
        </button>
    </form>
    <span style="margin-left:15px;color:#888">Total keys: <strong><?php echo $totalKeys; ?></strong></span>
</div>

<!-- Filters -->
<form method="GET" class="form-inline" style="margin-bottom:15px;padding:10px;background:#f5f5f5;border-radius:4px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">

    <label>Language:</label>
    <select name="filter_lang" class="form-control input-sm" style="width:140px;margin:0 10px">
        <option value="">All Languages</option>
        <?php foreach ($supportedLanguages as $lc => $li): ?>
        <option value="<?php echo $lc; ?>" <?php echo $filterLang === $lc ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($li['name']); ?>
        </option>
        <?php endforeach; ?>
    </select>

    <label>Search:</label>
    <input type="text" name="filter_search" class="form-control input-sm" style="width:200px;margin:0 10px"
           value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="key or translation text">

    <label style="margin:0 10px">
        <input type="checkbox" name="filter_empty" value="1" <?php echo $filterEmpty ? 'checked' : ''; ?>>
        Missing only
    </label>

    <label>Per page:</label>
    <select name="per_page" class="form-control input-sm" style="width:70px;margin:0 10px">
        <?php foreach ([25, 50, 75, 100] as $pp): ?>
        <option value="<?php echo $pp; ?>" <?php echo $perPage === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-default btn-sm"><i class="icon-search4"></i> Filter</button>
    <a href="Modules.php?modname=<?php echo urlencode($_REQUEST['modname']); ?>" class="btn btn-default btn-sm">Reset</a>
</form>

<!-- Add New Key -->
<?php $CSRF_TOKEN = CSRFSecure::CreateToken(); ?>
<form method="POST" class="form-inline" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="addkey">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
    <input type="text" name="new_key" class="form-control input-sm" placeholder="New translation key (e.g. _myNewLabel)" style="width:300px" required>
    <button type="submit" class="btn btn-info btn-sm"><i class="icon-plus3"></i> Add Key</button>
</form>

<!-- Pagination Top -->
<?php if ($totalFiltered > 0): ?>
<div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
    <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalFiltered); ?> of <?php echo $totalFiltered; ?> keys (page <?php echo $page; ?>/<?php echo $totalPages; ?>)</span>
    <div>
        <?php
        $baseUrl = "Modules.php?modname=" . urlencode($_REQUEST['modname'])
            . "&filter_lang=" . urlencode($filterLang)
            . "&filter_search=" . urlencode($filterSearch)
            . "&filter_empty=" . urlencode($filterEmpty)
            . "&per_page=$perPage";
        ?>
        <?php if ($page > 1): ?>
            <a href="<?php echo $baseUrl; ?>&page=1" class="btn btn-default btn-xs">First</a>
            <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>" class="btn btn-default btn-xs">Prev</a>
        <?php endif; ?>
        <?php
        $startP = max(1, $page - 3);
        $endP = min($totalPages, $page + 3);
        for ($p = $startP; $p <= $endP; $p++):
        ?>
            <?php if ($p === $page): ?>
                <span class="btn btn-primary btn-xs"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>&page=<?php echo $p; ?>" class="btn btn-default btn-xs"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>" class="btn btn-default btn-xs">Next</a>
            <a href="<?php echo $baseUrl; ?>&page=<?php echo $totalPages; ?>" class="btn btn-default btn-xs">Last</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Translation Table -->
<?php if (!empty($keyRows)): ?>
<div class="table-responsive">
<table class="table table-bordered table-condensed table-striped" id="translation-table">
    <thead>
        <tr style="background:#f0f0f0">
            <th style="width:220px;position:sticky;left:0;background:#f0f0f0;z-index:1">Key</th>
            <?php foreach ($displayLangs as $lc => $li): ?>
            <th style="min-width:250px">
                <?php echo htmlspecialchars($li['name']); ?> (<?php echo $lc; ?>)
                <?php if ($li['direction'] === 'rtl'): ?><span class="label label-warning" style="font-size:9px">RTL</span><?php endif; ?>
            </th>
            <?php endforeach; ?>
            <th style="width:60px">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($keyRows as $row):
        $key = $row['translation_key'];
        $rowToken = CSRFSecure::CreateToken();
    ?>
        <tr data-key="<?php echo htmlspecialchars($key); ?>">
            <td style="font-family:monospace;font-size:11px;word-break:break-all;position:sticky;left:0;background:#fff;z-index:1">
                <strong><?php echo htmlspecialchars($key); ?></strong>
            </td>
            <?php foreach ($displayLangs as $lc => $li):
                $val = $translations[$key][$lc] ?? '';
                $isEmpty = ($val === '' || $val === null);
                $dir = $li['direction'];
            ?>
            <td style="padding:2px;<?php echo $isEmpty ? 'background:#fff3cd' : ''; ?>">
                <textarea class="form-control input-sm"
                    name="trans_<?php echo $lc; ?>"
                    form="form_<?php echo md5($key); ?>"
                    dir="<?php echo $dir; ?>"
                    style="width:100%;min-height:34px;font-size:12px;resize:vertical;<?php echo $dir === 'rtl' ? 'text-align:right' : ''; ?>"
                    placeholder="<?php echo $isEmpty ? 'Missing translation' : ''; ?>"
                ><?php echo htmlspecialchars($val); ?></textarea>
            </td>
            <?php endforeach; ?>
            <td style="text-align:center;vertical-align:middle">
                <form id="form_<?php echo md5($key); ?>" method="POST" style="display:inline">
                    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
                    <input type="hidden" name="modfunc" value="save">
                    <input type="hidden" name="save_key" value="<?php echo htmlspecialchars($key); ?>">
                    <input type="hidden" name="TOKEN" value="<?php echo $rowToken; ?>">
                    <input type="hidden" name="filter_lang" value="<?php echo htmlspecialchars($filterLang); ?>">
                    <input type="hidden" name="filter_search" value="<?php echo htmlspecialchars($filterSearch); ?>">
                    <input type="hidden" name="filter_empty" value="<?php echo htmlspecialchars($filterEmpty); ?>">
                    <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                    <input type="hidden" name="page" value="<?php echo $page; ?>">
                    <button type="submit" class="btn btn-xs btn-success" title="Save all languages for this key">
                        <i class="icon-checkmark3"></i>
                    </button>
                </form>
                <?php $delToken = CSRFSecure::CreateToken(); ?>
                <form method="POST" style="display:inline;margin-top:3px" onsubmit="return confirm('Delete key from ALL languages?')">
                    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
                    <input type="hidden" name="modfunc" value="deletekey">
                    <input type="hidden" name="del_key" value="<?php echo htmlspecialchars($key); ?>">
                    <input type="hidden" name="TOKEN" value="<?php echo $delToken; ?>">
                    <button type="submit" class="btn btn-xs btn-danger" title="Delete key">
                        <i class="icon-cross2"></i>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<div class="alert alert-info">
    <?php if ($totalKeys === 0): ?>
        No translations in database. Click <strong>Import from Files</strong> to load existing language files.
    <?php else: ?>
        No keys match your filter criteria.
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Pagination Bottom -->
<?php if ($totalFiltered > $perPage): ?>
<div style="margin-top:10px;text-align:center">
    <?php if ($page > 1): ?>
        <a href="<?php echo $baseUrl; ?>&page=1" class="btn btn-default btn-xs">First</a>
        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>" class="btn btn-default btn-xs">Prev</a>
    <?php endif; ?>
    <?php for ($p = $startP; $p <= $endP; $p++): ?>
        <?php if ($p === $page): ?>
            <span class="btn btn-primary btn-xs"><?php echo $p; ?></span>
        <?php else: ?>
            <a href="<?php echo $baseUrl; ?>&page=<?php echo $p; ?>" class="btn btn-default btn-xs"><?php echo $p; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>" class="btn btn-default btn-xs">Next</a>
        <a href="<?php echo $baseUrl; ?>&page=<?php echo $totalPages; ?>" class="btn btn-default btn-xs">Last</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php PopTable('footer'); ?>

<script>
$(document).ready(function() {
    // Highlight changed textareas
    $('textarea[name^="trans_"]').on('input', function() {
        $(this).closest('td').css('background', '#d4edda');
    });
});
</script>

<?php
?>
