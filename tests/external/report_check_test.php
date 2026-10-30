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
use mod_simgen\local\generator;

/**
 * Tests of the web service mod_simgen_report_check.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(report_check::class)]
final class report_check_test extends \advanced_testcase {

    /**
     * Creates a simulation whose newest version was written by the AI and not checked yet.
     *
     * @param int $status generation status of the instance
     * @return array [module, version]
     */
    private function setup_simulation(int $status): array {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $version = $this->getDataGenerator()->get_plugin_generator('mod_simgen')->create_version($module,
            ['origin' => 'ai']);
        $DB->update_record('simgen', (object)[
            'id' => $module->id,
            'status' => $status,
            'pendingfeedback' => $status === generator::STATUS_PENDING ? 'Request of the teacher' : '',
            'pendingbaseversion' => 0,
            'genrequested' => 100,
        ]);
        $this->setUser($teacher);
        return [$module, $version];
    }

    /**
     * Reports a failed start-up check like the player does.
     *
     * @param \stdClass $module
     * @param \stdClass $version
     * @return array cleaned return value
     */
    private function report_failure(\stdClass $module, \stdClass $version): array {
        $result = report_check::execute($module->cmid, (int)$version->id, false, 'ReferenceError: x is not defined');
        return external_api::clean_returnvalue(report_check::execute_returns(), $result);
    }

    /**
     * The first failed check of an AI version starts an automatic repair.
     */
    public function test_failed_check_queues_repair(): void {
        global $DB;
        [$module, $version] = $this->setup_simulation(generator::STATUS_READY);

        $result = $this->report_failure($module, $version);

        $this->assertTrue($result['repairqueued']);
        $simgen = $DB->get_record('simgen', ['id' => $module->id], '*', MUST_EXIST);
        $this->assertEquals(generator::STATUS_PENDING, $simgen->status);
        $this->assertEquals($version->id, $simgen->pendingbaseversion);
        $this->assertEquals(1, $simgen->pendingautorepair);
    }

    /**
     * A request waiting for cron is not replaced by the automatic repair.
     */
    public function test_failed_check_keeps_pending_request(): void {
        global $DB;
        [$module, $version] = $this->setup_simulation(generator::STATUS_PENDING);

        $result = $this->report_failure($module, $version);

        $this->assertFalse($result['repairqueued']);
        $this->assertEquals(report_check::FAILED, $result['status']);
        $simgen = $DB->get_record('simgen', ['id' => $module->id], '*', MUST_EXIST);
        $this->assertEquals('Request of the teacher', $simgen->pendingfeedback);
        $this->assertEquals(0, $simgen->pendingbaseversion);
        $this->assertEquals(0, $simgen->pendingautorepair);
        $this->assertEquals(100, $simgen->genrequested);
    }
}
