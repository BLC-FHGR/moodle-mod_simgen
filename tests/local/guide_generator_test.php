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
 * Tests of the guide generator.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(guide_generator::class)]
final class guide_generator_test extends \advanced_testcase {

    /**
     * A run may only store the guide while no newer request was queued and no manual guide was saved.
     */
    public function test_owns_request(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $version = $this->getDataGenerator()->get_plugin_generator('mod_simgen')->create_version($module);
        $set = fn(int $status, int $requested) => $DB->update_record('simgen_versions',
            (object)['id' => $version->id, 'guidestatus' => $status, 'guiderequested' => $requested]);

        // The run claimed the request of time 100.
        $set(generator::STATUS_GENERATING, 100);
        $claimed = $DB->get_record('simgen_versions', ['id' => $version->id], '*', MUST_EXIST);
        $this->assertTrue(guide_generator::owns_request($claimed));

        // Marked as stale, no newer request: a late result is still welcome.
        $set(generator::STATUS_ERROR, 100);
        $this->assertTrue(guide_generator::owns_request($claimed));

        // The teacher saved a guide by hand meanwhile: it must not be overwritten.
        guide_generator::save_manual($claimed, 'Manual guide');
        $this->assertFalse(guide_generator::owns_request($claimed));

        // A newer request was queued.
        $set(generator::STATUS_PENDING, 200);
        $this->assertFalse(guide_generator::owns_request($claimed));
    }
}
