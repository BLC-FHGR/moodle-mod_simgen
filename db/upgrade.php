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
 * Upgrade steps for mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_simgen upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_simgen_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026101300) {
        // Live conversations: personas of a version and the conversation table.
        $table = new xmldb_table('simgen_versions');
        $field = new xmldb_field('personas', XMLDB_TYPE_TEXT, null, null, null, null, null, 'css');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('simgen_conversations');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('simgenid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('personaid', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('messagedata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('signaldata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('turncount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('resetcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('simgenid', XMLDB_KEY_FOREIGN, ['simgenid'], 'simgen', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('simgen-user-version-persona', XMLDB_INDEX_UNIQUE,
            ['simgenid', 'userid', 'versionid', 'personaid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026101300, 'simgen');
    }

    if ($oldversion < 2026101900) {
        // Facilitator guide per version.
        $table = new xmldb_table('simgen_versions');
        $fields = [
            new xmldb_field('guide', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timecreated'),
            new xmldb_field('guidestatus', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'guide'),
            new xmldb_field('guidemessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'guidestatus'),
            new xmldb_field('guideorigin', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'guidemessage'),
            new xmldb_field('guidemodelinfo', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'guideorigin'),
            new xmldb_field('guiderequested', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'guidemodelinfo'),
            new xmldb_field('guidestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'guiderequested'),
            new xmldb_field('guidemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'guidestarted'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026101900, 'simgen');
    }

    return true;
}
