# openSIS Classic Test Suite

## Overview

PHPUnit 11 test suite with **332 tests** and **575 assertions**, plus **62 Playwright E2E browser tests**.

### Requirements

- PHP 8.x
- Composer

### Setup

```bash
composer install
```

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# With verbose output
./vendor/bin/phpunit --testdox

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests only
./vendor/bin/phpunit --testsuite Integration

# Single test file
./vendor/bin/phpunit tests/Unit/PasswordHashTest.php

# Filter by test name
./vendor/bin/phpunit --filter testBlocksSqlInjection
```

---

## Test Structure

```
tests/
├── bootstrap.php                              # Autoloader, session init, shared includes
├── TsvMock.php                                # TSV-based DB mock infrastructure
├── Unit/                                      # Isolated function-level tests (280 tests)
│   ├── PasswordHashTest.php                   #   9 tests
│   ├── CSRFSecurityTest.php                   #  11 tests
│   ├── SqlSecurityFilterTest.php              #  22 tests
│   ├── CleanParamTest.php                     #  24 tests
│   ├── MonthNwSwitchTest.php                  #  30 tests
│   ├── LangDirectionTest.php                  #   8 tests
│   ├── PercentTest.php                        #  10 tests
│   ├── DbDateTest.php                         #  13 tests
│   ├── SortFncTest.php                        #  10 tests
│   ├── BrowserTest.php                        #   6 tests
│   ├── ButtonsTest.php                        #   3 tests
│   ├── ReindexResultsTest.php                 #   4 tests
│   ├── ErrorMessageTest.php                   #   8 tests
│   ├── ShowVarTest.php                        #   4 tests
│   ├── DrawTabTest.php                        #   8 tests
│   ├── DrawHeaderTest.php                     #   5 tests
│   ├── PopTableTest.php                       #   4 tests
│   ├── UrlFncTest.php                         #   5 tests
│   ├── PreparePhpSelfTest.php                 #   5 tests
│   ├── TsvMockTest.php                        #  11 tests (DB mock)
│   ├── ConfigFncTest.php                      #   4 tests (DB mock)
│   ├── DeCodedsTest.php                       #   6 tests (DB mock)
│   ├── UserFncTest.php                        #   8 tests (DB mock)
│   ├── AllowEditTest.php                      #  14 tests (DB mock)
│   └── SqliteAdapterTest.php                  #  16 tests (SQLite)
└── Integration/                               # Multi-component workflow tests (52 tests)
    ├── AuthenticationWorkflowTest.php         #   8 tests
    ├── InputValidationWorkflowTest.php        #  16 tests
    ├── SessionSecurityWorkflowTest.php        #  10 tests
    ├── LanguageWorkflowTest.php               #  10 tests
    ├── DateWorkflowTest.php                   #  10 tests
    └── DBGetWorkflowTest.php                  #  12 tests (TSV mock)

e2e/                                           # Playwright E2E browser tests (62 tests)
├── fixtures.ts                                # Login/navigate helpers
├── auth.spec.ts                               #  10 tests
├── navigation.spec.ts                         #   4 tests
├── school-setup.spec.ts                       #   8 tests
├── students.spec.ts                           #   6 tests
├── users.spec.ts                              #   6 tests
├── scheduling.spec.ts                         #   6 tests
├── grades.spec.ts                             #   7 tests
├── attendance.spec.ts                         #   5 tests
├── messaging.spec.ts                          #   5 tests
├── tools.spec.ts                              #   7 tests
├── eligibility.spec.ts                        #   3 tests
└── rtl.spec.ts                                #   7 tests
```

---

## Unit Tests

### PasswordHashTest (9 tests)

**Source:** `functions/PasswordHashFnc.php`
**Functions:** `GenerateNewHash()`, `VerifyHash()`

| Test | What it verifies |
|------|------------------|
| `testGenerateNewHashReturnsNonEmptyString` | Hash generation produces output |
| `testGenerateNewHashProducesBcryptHash` | Output uses `$2y$` bcrypt format |
| `testVerifyHashReturnsTrueForCorrectPassword` | Correct password matches hash |
| `testVerifyHashReturnsFalseForWrongPassword` | Wrong password is rejected |
| `testVerifyHashReturnsFalseForEmptyPassword` | Empty string is rejected |
| `testGenerateNewHashProducesUniqueHashes` | Same password produces different hashes (random salt) |
| `testSpecialCharactersInPassword` | Symbols `@$!#%^&*` in passwords work |
| `testUnicodePassword` | Arabic/Unicode passwords hash and verify correctly |
| `testLongPassword` | 1000-character passwords work (bcrypt truncates at 72 bytes) |

### CSRFSecurityTest (11 tests)

**Source:** `functions/CSRFSecurityFnc.php`
**Class:** `CSRFSecure`

| Test | What it verifies |
|------|------------------|
| `testCreateTokenReturnsHexString` | Token is 64-char hex string (32 random bytes) |
| `testCreateTokenStoresInSession` | Token stored in `$_SESSION['_TOKEN']` |
| `testCreateTokenSetsExpiryInFuture` | Expiry timestamp is in the future |
| `testCreateTokenFieldOutputsHiddenInput` | Outputs `<input type='hidden' name='TOKEN'>` |
| `testValidateTokenReturnsTrueForValidToken` | Matching token + POST validates |
| `testValidateTokenDestroysTokenAfterUse` | Session token removed after validation |
| `testValidateTokenReturnsFalseForWrongToken` | Mismatched token rejected |
| `testValidateTokenReturnsFalseWhenNoSessionToken` | No session token = rejection |
| `testValidateTokenReturnsFalseWhenNoPostToken` | No POST token = rejection |
| `testValidateTokenReturnsFalseForExpiredToken` | Expired token (>60s) rejected |
| `testCreateTokenGeneratesUniqueTokens` | Two calls produce different tokens |
| `testTokenCannotBeReused` | Second validation with same token fails |

### SqlSecurityFilterTest (22 tests)

**Source:** `functions/SqlSecurityFnc.php`
**Function:** `sqlSecurityFilter()`

| Test | Attack vector blocked |
|------|----------------------|
| `testCleanStringPassesThrough` | Normal text is preserved |
| `testEmptyStringReturnsEmpty` | Empty input returns empty |
| `testBlocksUnionSelect` | `UNION SELECT` SQL injection |
| `testBlocksDropTable` | `DROP TABLE` |
| `testBlocksDeleteStatement` | `DELETE FROM` |
| `testBlocksInsertStatement` | `INSERT INTO` |
| `testBlocksTruncate` | `TRUNCATE TABLE` |
| `testBlocksSleepInjection` | `sleep(5)` time-based injection |
| `testBlocksSemicolon` | Statement termination `;` |
| `testBlocksDirectoryTraversal` | `../../../etc/passwd` |
| `testBlocksUrlEncodedTraversal` | `..%2f` encoded traversal |
| `testBlocksSqlComment` | `'--` comment injection |
| `testBlocksUrlEncodedUnion` | `union%20select%20` |
| `testStripsHtmlTags` | `<b>bold</b>` XSS |
| `testEncodesHtmlEntities` | Quote encoding to `&quot;` |
| `testBlocksConcatFunction` | `concat()` data extraction |
| `testBlocksExtractFunction` | `extract` keyword |
| `testBlocksSkipGrantTables` | MySQL privilege escalation |
| `testHandlesArrayInput` | Array values filtered recursively |
| `testFiltersMaliciousArrayValues` | Malicious array entries removed |
| `testHandlesEmptyArray` | Empty array returns empty array |
| `testHandlesNestedArrays` | Nested arrays processed recursively |
| `testHandlesObjectInput` | stdClass objects sanitized |
| `testReturnsEmptyForUnknownTypes` | null/unknown types return empty |
| `testCaseInsensitiveBlocking` | `UnIoN SeLeCt` mixed case blocked |
| `testSafeNumericString` | Numbers pass through |
| `testSafeAlphanumericString` | Alphanumeric strings pass through |

### CleanParamTest (24 tests)

**Source:** `functions/ParamLibFnc.php`
**Functions:** `clean_param()`, `optional_param()`

| Test | PARAM type | What it verifies |
|------|------------|------------------|
| `testRawReturnsUnchanged` | `PARAM_RAW` | No modification at all |
| `testIntCastsToInteger` | `PARAM_INT` | `'42'` → `42` |
| `testIntWithTextReturnsZero` | `PARAM_INT` | `'abc'` → `0` |
| `testIntWithFloatTruncates` | `PARAM_INT` | `'3.14'` → `3` |
| `testIntWithNegative` | `PARAM_INT` | `'-5'` → `-5` |
| `testIntWithMixedString` | `PARAM_INT` | `'123abc'` → `123` |
| `testNumberCastsToFloat` | `PARAM_NUMBER` | `'3.14'` → `3.14` |
| `testNumberWithInteger` | `PARAM_NUMBER` | `'42'` → `42.0` |
| `testAlphaStripsNumbers` | `PARAM_ALPHA` | `'hello123'` → `'hello'` |
| `testAlphaStripsSpecialChars` | `PARAM_ALPHA` | `'te$st!'` → `'test'` |
| `testAlphaAllowsHash` | `PARAM_ALPHA` | `#` character preserved |
| `testAlphaPreservesCase` | `PARAM_ALPHA` | Case not modified |
| `testAlphanumAllowsLettersAndNumbers` | `PARAM_ALPHANUM` | `'Test123'` passes |
| `testAlphanumStripsSpecialChars` | `PARAM_ALPHANUM` | `@!` stripped |
| `testBoolOnReturnsOne` | `PARAM_BOOL` | `'on'` → `1` |
| `testBoolYesReturnsOne` | `PARAM_BOOL` | `'yes'` → `1` |
| `testBoolOffReturnsZero` | `PARAM_BOOL` | `'off'` → `0` |
| `testBoolNoReturnsZero` | `PARAM_BOOL` | `'no'` → `0` |
| `testBoolEmptyReturnsZero` | `PARAM_BOOL` | `''` → `0` |
| `testBoolTruthyStringReturnsOne` | `PARAM_BOOL` | Any non-empty → `1` |
| `testNotagsStripsHtml` | `PARAM_NOTAGS` | HTML tags removed |
| `testNotagsStripsScriptTags` | `PARAM_NOTAGS` | `<script>` fully stripped |
| `testFileCleanesTraversal` | `PARAM_FILE` | `../../` removed |
| `testFileStripsControlChars` | `PARAM_FILE` | Null bytes removed |
| `testFileStripsBackslash` | `PARAM_FILE` | `\` removed |
| `testPathNormalizesSlashes` | `PARAM_PATH` | `\` → `/` |
| `testPathCleansTraversal` | `PARAM_PATH` | `../` removed |
| `testSequenceAllowsNumbersAndCommas` | `PARAM_SEQUENCE` | `'1,2,3'` passes |
| `testSequenceStripsLetters` | `PARAM_SEQUENCE` | Letters removed |
| `testMailAllowsValidEmail` | `PARAM_MAIL` | Valid email passes |
| `testMailStripsInvalidChars` | `PARAM_MAIL` | `<>` stripped |
| `testPhoneAllowsValidFormat` | `PARAM_PHONE` | `+1(555)123-4567` passes |
| `testPhoneStripsLetters` | `PARAM_PHONE` | Letters stripped |
| `testArrayCleaningRecursive` | Array | Recursively applies type |
| `testSpclRemovesDangerousChars` | `PARAM_SPCL` | `*@#` removed |
| `testOptionalParamReturnsDefaultWhenMissing` | — | Default value returned |
| `testOptionalParamReadsFromPost` | — | POST read correctly |
| `testOptionalParamReadsFromGet` | — | GET read correctly |
| `testOptionalParamPostTakesPrecedence` | — | POST wins over GET |

### MonthNwSwitchTest (30 tests)

**Source:** `functions/MonthNwSwitchFnc.php`
**Functions:** `MonthNWSwitch()`, `__mnwswitch_num2char()`, `__mnwswitch_char2num()`

| Test | What it verifies |
|------|------------------|
| `testNumToChar` (x12) | `'01'`→`'JAN'` through `'12'`→`'DEC'` |
| `testCharToNum` (x12) | `'JAN'`→`'01'` through `'DEC'`→`'12'` |
| `testSingleDigitMonthPadded` | `'1'` padded to `'01'` then converted |
| `testCaseInsensitiveCharToNum` | `'jan'` and `'Jun'` work |
| `testAlreadyNumReturnsNum` | `'05'` in tonum returns `'05'` |
| `testAlreadyCharReturnsChar` | `'JAN'` in tochar returns `'JAN'` |
| `testBothDirectionRoundTrips` | num → char → num preserves value |
| `testDecemberZeroEdgeCase` | `'00'` maps to `'DEC'` |

### LangDirectionTest (7 tests)

**Source:** `functions/langFnc.php`
**Function:** `langDirection()`

| Test | What it verifies |
|------|------------------|
| `testReturnsLtrForEnglish` | `en` → `'ltr'` |
| `testReturnsLtrForFrench` | `fr` → `'ltr'` |
| `testReturnsLtrForSpanish` | `es` → `'ltr'` |
| `testReturnsRtlForArabic` | `ar` → `'rtl'` |
| `testReturnsLtrWhenNoSessionLanguage` | Missing session → `'ltr'` default |
| `testReturnsLtrForUnsupportedLanguage` | `'xx'` → `'ltr'` default |
| `testReturnsLtrForEmptyLanguage` | `''` → `'ltr'` default |

### PercentTest (10 tests)

**Source:** `functions/PercentFnc.php`
**Function:** `Percent()`

| Test | Input | Expected |
|------|-------|----------|
| `testBasicPercentage` | `0.85` | `'85%'` |
| `testZeroPercent` | `0` | `'0%'` |
| `testHundredPercent` | `1` | `'100%'` |
| `testOverHundredPercent` | `1.5` | `'150%'` |
| `testDecimalPrecision` | `0.857142` | `'85.71%'` |
| `testCustomDecimals` | `0.857142, 3` | `'85.714%'` |
| `testZeroDecimals` | `0.857, 0` | `'86%'` |
| `testNegativePercent` | `-0.5` | `'-50%'` |
| `testSmallFraction` | `0.005` | `'0.5%'` |
| `testVerySmallValue` | `0.0001` | `'0.01%'` |

---

## Integration Tests

### AuthenticationWorkflowTest (8 tests)

**Workflow:** CSRF token generation → password verification → session setup → logout

| Test | Scenario |
|------|----------|
| `testFullLoginWorkflow` | Complete login: CSRF valid + password matches → session populated |
| `testLoginFailsWithWrongPassword` | CSRF valid but wrong password → no session |
| `testLoginFailsWithInvalidCsrf` | Forged CSRF token → rejected before password check |
| `testLoginFailsWithExpiredCsrf` | Token older than 60 seconds → rejected |
| `testCsrfTokenNotReusableAcrossLoginAttempts` | Replayed token fails on second use |
| `testLogoutClearsSession` | Session keys (STAFF_ID, USERNAME, etc.) removed |
| `testSessionFixationPrevention` | Session ID changes after login (`session_regenerate_id`) |
| `testMultipleLoginAttemptsWithFreshTokens` | Failed then successful login with new tokens |

### InputValidationWorkflowTest (16 tests)

**Workflow:** User form input → `optional_param()` / `clean_param()` → `sqlSecurityFilter()`

| Test | Attack / Input |
|------|----------------|
| `testSafeStudentNamePassesBothFilters` | Normal name passes through |
| `testSqlInjectionInNameBlocked` | `Robert'; DROP TABLE students;--` |
| `testXssInTextFieldBlocked` | `<script>document.cookie</script>` |
| `testIntegerParamBlocksInjection` | `1 OR 1=1` → cast to `1` |
| `testModulePathTraversalBlocked` | `../../../etc/passwd` |
| `testBulkFormSubmissionFiltered` | Mix of safe/unsafe fields |
| `testSearchQuerySanitized` | `John <b>Smith</b>` → tags stripped |
| `testEmailParameterCleaned` | Valid email preserved |
| `testMaliciousEmailBlocked` | Email with `<script>` appended |
| `testPhoneParameterCleaned` | `+1(555)123-4567` preserved |
| `testMissingFieldsUseDefaults` | Absent params return defaults |
| `testSequenceParameterFiltered` | `1,2,3,4,5` preserved |
| `testSequenceWithInjectionFiltered` | `1,2,3; DROP TABLE` → digits only |
| `testBooleanToggleParameter` | `on`/`off` → `1`/`0` |
| `testUnicodeInputPreserved` | Arabic text in PARAM_RAW |
| `testDoubleEncodedTraversalBlocked` | `../` traversal blocked |

### SessionSecurityWorkflowTest (10 tests)

**Workflow:** Access control checks, role-based menus, session management

| Test | Scenario |
|------|----------|
| `testUnauthenticatedUserHasNoAccess` | No session → access denied |
| `testAuthenticatedStaffHasAccess` | STAFF_ID set → access granted |
| `testAuthenticatedStudentHasAccess` | STUDENT_ID set → access granted |
| `testCsrfProtectionOnFormSubmission` | Valid token passes |
| `testCsrfProtectionBlocksCrossOriginSubmit` | Forged token blocked |
| `testSessionContextConsistency` | All session vars populated together |
| `testRoleBasedMenuAccess` | Admin sees tools, teacher does not |
| `testSchoolContextSwitching` | School ID changes, year persists |
| `testPasswordChangeWorkflow` | Old verify → new hash → old fails → new succeeds |
| `testMultipleFormSubmitsRequireFreshTokens` | Each form needs its own token |

### LanguageWorkflowTest (10 tests)

**Workflow:** Language selection → cookie/session → direction → HTML dir attribute

| Test | Scenario |
|------|----------|
| `testSupportedLanguagesFileLoads` | `supportedLanguages.php` returns all 4 languages |
| `testSupportedLanguagesHaveRequiredKeys` | Each entry has `name` and `direction` |
| `testLanguageSelectionFromRequestSetsSession` | `$_REQUEST['language'] = 'ar'` → session + RTL |
| `testLanguageFallbackToCookie` | No request, cookie `remember_me_lang` used |
| `testLanguageFallbackToEnglishDefault` | No request, no cookie → English |
| `testInvalidLanguageRequestFallsBackToDefault` | `'xx_INVALID'` → English |
| `testInvalidCookieLangIgnored` | Malicious cookie value → English |
| `testRtlDirectionDeterminesHtmlAttribute` | Arabic → `dir="rtl"` |
| `testLtrDirectionDeterminesHtmlAttribute` | English → `dir="ltr"` |
| `testAllLanguageFilesExist` | `lang_en.php`, `lang_ar.php`, etc. all present |

### DateWorkflowTest (10 tests)

**Workflow:** Date parsing, month conversion, time formatting for scheduling/attendance

| Test | Scenario |
|------|----------|
| `testMysqlDateMonthExtraction` | `'2025-09-15'` → month `'09'` → `'SEP'` |
| `testOracleDateMonthExtraction` | `'15-SEP-25'` → month `'SEP'` → `'09'` |
| `testDateComponentParsing` | MySQL date split into year/month/day |
| `testOracleDateComponentParsing` | Oracle date split into day/month/year |
| `testMonthRoundTripConversion` | All 12 months: num → char → num |
| `testAllMonthConversions` | Complete mapping table verified |
| `testProperTimeFormatsCorrectly` | `'08:30:00'` → `'8:30 AM'`, `'13:00'` → `'1:00 PM'` |
| `testProperTimeWithDifferentFormats` | Short time format `'15:45'` → `'3:45 PM'` |
| `testDateComparisonForAttendance` | Date range checks for marking periods |
| `testSchoolYearDateRangeValidation` | School year boundary validation |

---

## Configuration

### phpunit.xml

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

### bootstrap.php

Loads:
- Composer autoloader
- Session initialization
- `ParamLibFnc.php` (PARAM_* constants + `clean_param` + `optional_param`)
- `MonthNwSwitchFnc.php` (month conversion, dependency for date tests)
- `PragRepFnc.php` (regex helper `par_rep()`, dependency for `clean_param`)
- `TsvMock.php` (TSV-based DB mock — overrides `db_fetch_row`/`DBQuery`)
- `DbGetFnc.php` (uses the mocked `db_fetch_row`)

---

## Adding New Tests

1. Create a test file in `tests/Unit/` or `tests/Integration/`
2. Extend `PHPUnit\Framework\TestCase`
3. Use `require_once TEST_ROOT . '/functions/YourFnc.php'` to load the function under test
4. Name test methods starting with `test`
5. Run with `./vendor/bin/phpunit`

### Example

```php
<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/YourFnc.php';

class YourFncTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertEquals('expected', yourFunction('input'));
    }
}
```

---

## Coverage Gaps (Future Work)

DB-dependent functions now testable via TSV mock or SQLite. Remaining gaps:

| Area | Reason | Priority |
|------|--------|----------|
| `SaveData()` | Complex INSERT/UPDATE generation | Medium |
| `GetStuList()` | Complex SQL generation (134KB) | Medium |
| `SearchFnc.php` | Session + DB dependent (80KB) | Medium |
| `ListOutput()` | UI rendering (1MB file) | Low |
| `AttendanceFnc.php` | DB-dependent attendance updates | Low |
| `CustomFieldsFnc.php` | Dynamic field handling, needs DB | Low |
| `_makeLetterGrade()` | Needs grade scale config from DB | Low |
| `Currency()` | DB lookup for currency setting | Low |

### Already Covered via TSV Mock / SQLite

| Area | Test Suite | Tests |
|------|-----------|-------|
| `DBGet()` / `db_fetch_row()` | TsvMockTest, DBGetWorkflowTest | 23 |
| `AllowEdit()` / `AllowUse()` | AllowEditTest | 14 |
| `User()` / `Preferences()` | UserFncTest | 8 |
| `Config()` | ConfigFncTest | 4 |
| `DeCodeds()` / `cleanParamMod()` | DeCodedsTest | 6 |
| SQLite adapter | SqliteAdapterTest | 16 |
