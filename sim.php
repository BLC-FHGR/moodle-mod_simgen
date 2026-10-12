<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Delivers the simulation document for the sandboxed iframe.
 *
 * The response carries a CSP with the sandbox directive, so the generated code always runs with an opaque
 * origin (no access to the Moodle session, cookies or DOM), even if this URL is opened directly.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\local\html_builder;

$id = required_param('id', PARAM_INT);
$versionid = optional_param('version', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm, false, true);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:view', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$canmanage = has_capability('mod/simgen:manage', $context);

// Students only ever get the published version; teachers may preview drafts.
if (!$canmanage || !$versionid) {
    $versionid = (int)$simgen->publishedversion;
}
$version = $versionid ? $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id]) : false;
if (!$version) {
    throw new moodle_exception('error_noversion', 'mod_simgen');
}

// Release the session lock: the AJAX calls of the player must not wait for this request.
\core\session\manager::write_close();

$html = html_builder::build($simgen, $version, $canmanage && !$download);

if ($download && $canmanage) {
    // Standalone file (runs without Moodle; progress is not saved).
    $filename = clean_filename(format_string($simgen->name, true, ['escape' => false]) . '-v' . $version->versionnum . '.html');
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    echo $html;
    die;
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Security-Policy: ' . html_builder::csp());
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: private, no-store, max-age=0');
echo $html;
