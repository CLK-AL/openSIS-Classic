# openSIS Classic
Community Edition version 9.3 (Rel date: 06/02/2026)
Created by [OS4ED](https://www.os4ed.com/)

openSIS is an easy to use Student Information System for organizing student information and school-related operations to promote efficiency in K-12, trade schools and higher education school systems.

## Key Features

- Manage Student Data
- Manage Staff Data
- Manage School Data
- Course Manager
- Scheduling
- Attendance
- Grades
- Teacher Gradebook
- Progress Reports
- Report Cards
- Transcripts
- Built-in Communication
- Bulk data imports

## Installation

openSIS Community Edition requires
- Apache 2.4 or above
- MySQL 5.7, 8.0 or Maria DB 10.4.x
- PHP 8.x

[Installation Details](https://github.com/OS4ED/openSIS-Classic/blob/master/docs/openSIS-CE%20Installation%20Guide.pdf)


## RTL (Right-to-Left) Language Support

openSIS includes built-in support for RTL languages such as Arabic. RTL layout is activated automatically when a user selects an RTL language at the login screen.

### Supported RTL Languages
- Arabic (ar)

### How It Works
1. Select a language from the dropdown on the login page
2. The system stores the language preference in the session and optionally in a cookie
3. Pages automatically set `dir="rtl"` on the HTML element and load RTL-specific CSS

### Adding a New RTL Language
1. Add a translation file `lang/lang_XX.php` (where XX is the language code)
2. Register the language in `lang/supportedLanguages.php` with `'direction' => 'rtl'`
3. The system will automatically apply RTL layout for the new language

### RTL CSS
RTL-specific styles are in `assets/css/rtl.css`. This file is conditionally loaded only when an RTL language is active. It provides Bootstrap grid, form, navbar, table, dropdown, and modal RTL overrides.

## License

openSIS is an Open Source Project licensed under the GNU General Public License, the full license can be found [here](https://github.com/OS4ED/openSIS-Classic/blob/master/docs/License.txt).
