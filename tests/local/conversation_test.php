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

namespace mod_simgen\local;

/**
 * Tests of the live conversations.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(conversation::class)]
final class conversation_test extends \advanced_testcase {

    /**
     * Creates a simulation with two versions, the second one published.
     *
     * @return array [simgen, context, v1, v2, student, teacher]
     */
    private function setup_simulation(): array {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_simgen');
        $v1 = $generator->create_version($module);
        $v2 = $generator->create_version($module);
        $DB->set_field('simgen', 'publishedversion', $v2->id, ['id' => $module->id]);
        $simgen = $DB->get_record('simgen', ['id' => $module->id], '*', MUST_EXIST);
        return [$simgen, \context_module::instance($module->cmid), $v1, $v2, $student, $teacher];
    }

    /**
     * Learners talk to the published version; teachers may preview other versions.
     */
    public function test_resolve_version(): void {
        [$simgen, $context, $v1, $v2, $student, $teacher] = $this->setup_simulation();

        $this->setUser($student);
        $this->assertEquals($v2->id, conversation::resolve_version($simgen, 0, $context)->id);
        $this->assertEquals($v2->id, conversation::resolve_version($simgen, (int)$v2->id, $context)->id);

        $this->setUser($teacher);
        $this->assertEquals($v1->id, conversation::resolve_version($simgen, (int)$v1->id, $context)->id);
        $this->assertEquals($v2->id, conversation::resolve_version($simgen, 0, $context)->id);
    }

    /**
     * A learner whose page still shows an older version must reload instead of talking to the new version.
     */
    public function test_resolve_version_outdated_page(): void {
        [$simgen, $context, $v1, , $student] = $this->setup_simulation();

        $this->setUser($student);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error_versionchanged', 'mod_simgen'));
        conversation::resolve_version($simgen, (int)$v1->id, $context);
    }
}
