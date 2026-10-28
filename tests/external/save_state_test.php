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

namespace mod_simgen\external;

use core_external\external_api;

/**
 * Tests of the web service mod_simgen_save_state.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(save_state::class)]
final class save_state_test extends \advanced_testcase {

    /**
     * Calls the web service like the player does.
     *
     * @param int $cmid
     * @param int $versionid version shown on the page
     * @param string $state
     * @param bool $completed
     * @param int $activeseconds
     * @return array cleaned return value
     */
    private function save(int $cmid, int $versionid, string $state, bool $completed = false, int $activeseconds = 0): array {
        $result = save_state::execute($cmid, $state, $completed, $completed ? 3.0 : null, $completed ? 4.0 : null, '',
            $activeseconds, $versionid);
        return external_api::clean_returnvalue(save_state::execute_returns(), $result);
    }

    /**
     * After a new version was published, the state of the old code is not stored, but completion and time are.
     */
    public function test_save_after_version_change(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_simgen');
        $v1 = $generator->create_version($module);
        $v2 = $generator->create_version($module);
        $DB->set_field('simgen', 'publishedversion', $v1->id, ['id' => $module->id]);
        $this->setUser($student);
        $conditions = ['simgenid' => $module->id, 'userid' => $student->id];

        // Page shows the published version.
        $result = $this->save($module->cmid, (int)$v1->id, '{"step":1}');
        $this->assertFalse($result['versionchanged']);
        $this->assertEquals('{"step":1}', $DB->get_field('simgen_states', 'statedata', $conditions));

        // The teacher publishes v2 while the page of v1 is still open.
        $DB->set_field('simgen', 'publishedversion', $v2->id, ['id' => $module->id]);
        $result = $this->save($module->cmid, (int)$v1->id, '{"step":5}');
        $this->assertTrue($result['versionchanged']);
        $this->assertEquals('{"step":1}', $DB->get_field('simgen_states', 'statedata', $conditions));

        $result = $this->save($module->cmid, (int)$v1->id, '{"step":6}', true, 60);
        $this->assertTrue($result['versionchanged']);
        $record = $DB->get_record('simgen_states', $conditions, '*', MUST_EXIST);
        $this->assertEquals('{"step":1}', $record->statedata);
        $this->assertEquals(1, $record->completed);
        $this->assertEquals(3, $record->rawscore);
        $this->assertEquals(60, $record->activetime);

        // After reloading, the page shows v2 and saves again.
        $result = $this->save($module->cmid, (int)$v2->id, '{"step":0}');
        $this->assertFalse($result['versionchanged']);
        $record = $DB->get_record('simgen_states', $conditions, '*', MUST_EXIST);
        $this->assertEquals('{"step":0}', $record->statedata);
        $this->assertEquals($v2->id, $record->versionid);

        // Callers without a version (e.g. a cached player of an older plugin version) are not checked.
        $result = $this->save($module->cmid, 0, '{"step":2}');
        $this->assertFalse($result['versionchanged']);
        $this->assertEquals('{"step":2}', $DB->get_field('simgen_states', 'statedata', $conditions));
    }
}
