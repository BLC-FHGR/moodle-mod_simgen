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
 * Tests of the generator.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(generator::class)]
final class generator_test extends \advanced_testcase {

    /** @var string persona block of the base version */
    const BASE_PERSONAS = '{"personas": [{"id": "gast", "name": "Frau Keller", "prompt": "Du bist ein Hotelgast."}]}';

    /**
     * Parsed AI response.
     *
     * @param string $personas raw persona JSON ('' if the response has no persona block)
     * @param string $codetype
     * @return array
     */
    private function parsed(string $personas, string $codetype = 'react'): array {
        return ['codetype' => $codetype, 'sourcecode' => 'function App() {}', 'css' => '', 'personas' => $personas,
            'timeplan' => ''];
    }

    /**
     * A revision without persona block keeps the personas of the base version.
     */
    public function test_personas_revision_without_block_keeps_base(): void {
        $simgen = (object)['simtype' => 'auto'];
        $base = (object)['codetype' => 'react', 'personas' => self::BASE_PERSONAS];

        $personas = generator::personas_from_response($this->parsed(''), $simgen, $base);

        $this->assertSame(['gast'], array_keys($personas));
        $this->assertSame('Frau Keller', $personas['gast']['name']);
    }

    /**
     * Personas in the response replace those of the base version.
     */
    public function test_personas_revision_with_block_replaces_base(): void {
        $simgen = (object)['simtype' => 'conversation'];
        $base = (object)['codetype' => 'react', 'personas' => self::BASE_PERSONAS];
        $new = '{"personas": [{"id": "chef", "name": "Herr Huber", "prompt": "Du bist der Chef."}]}';

        $personas = generator::personas_from_response($this->parsed($new), $simgen, $base);

        $this->assertSame(['chef'], array_keys($personas));
    }

    /**
     * A fresh conversation simulation needs personas in the response.
     */
    public function test_personas_fresh_generation_without_block_fails(): void {
        $this->expectException(\moodle_exception::class);
        generator::personas_from_response($this->parsed(''), (object)['simtype' => 'conversation'], null);
    }

    /**
     * A revision of a conversation simulation without personas anywhere still fails.
     */
    public function test_personas_revision_without_any_personas_fails(): void {
        $base = (object)['codetype' => 'react', 'personas' => null];
        $this->expectException(\moodle_exception::class);
        generator::personas_from_response($this->parsed(''), (object)['simtype' => 'conversation'], $base);
    }

    /**
     * A run may only store its result while no newer request was queued.
     */
    public function test_owns_request(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $set = fn(int $status, int $requested) => $DB->update_record('simgen',
            (object)['id' => $module->id, 'status' => $status, 'genrequested' => $requested]);

        // The run claimed the request of time 100.
        $set(generator::STATUS_GENERATING, 100);
        $claimed = $DB->get_record('simgen', ['id' => $module->id], '*', MUST_EXIST);
        $this->assertTrue(generator::owns_request($claimed));

        // Marked as stale, no newer request: a late result is still welcome.
        generator::check_stale((object)['id' => $module->id, 'status' => generator::STATUS_GENERATING,
            'genstarted' => 1]);
        $this->assertTrue(generator::owns_request($claimed));

        // The teacher retried: the late result must not touch the new request.
        $set(generator::STATUS_PENDING, 200);
        $this->assertFalse(generator::owns_request($claimed));
        $set(generator::STATUS_GENERATING, 200);
        $this->assertFalse(generator::owns_request($claimed));
    }

    /**
     * New versions get the next free number, even if the number the caller computed earlier is taken meanwhile.
     */
    public function test_insert_version(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('simgen', ['course' => $course->id]);
        $this->getDataGenerator()->get_plugin_generator('mod_simgen')->create_version($module);

        // Computed before a parallel request stored version 2.
        $version = (object)['simgenid' => $module->id, 'versionnum' => 2, 'codetype' => 'react',
            'sourcecode' => 'function App() {}', 'origin' => 'manual', 'userid' => 0, 'timecreated' => time()];
        $this->getDataGenerator()->get_plugin_generator('mod_simgen')->create_version($module);

        $id = generator::insert_version($version);

        $this->assertEquals(3, $DB->get_field('simgen_versions', 'versionnum', ['id' => $id]));
        $this->assertEquals(3, $version->versionnum);
    }

    /**
     * Simulations without live conversations have no personas.
     */
    public function test_personas_not_a_conversation(): void {
        $this->assertSame([], generator::personas_from_response($this->parsed(''), (object)['simtype' => 'auto'], null));
        $this->assertSame([], generator::personas_from_response($this->parsed('', 'html'),
            (object)['simtype' => 'conversation'], null));
    }
}
