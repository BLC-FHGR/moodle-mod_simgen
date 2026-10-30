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
 * Tests of the HTML builder.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(html_builder::class)]
final class html_builder_test extends \advanced_testcase {

    /**
     * All libraries of the runtime are marked, so bridge.js can tell a network problem from an error in the code.
     */
    public function test_libraries_are_marked(): void {
        $simgen = (object)['name' => 'Statik', 'simlanguage' => 'de'];
        $version = (object)[
            'codetype' => 'react',
            'sourcecode' => 'function App() { return <MathBoard /> && <Formula tex="x" />; }',
            'css' => '',
            'personas' => null,
            'compiled' => null,
            'compiledhash' => null,
        ];

        $html = html_builder::build($simgen, $version, true);

        // React, ReactDOM, Babel, KaTeX and JSXGraph (scripts) plus the two stylesheets.
        preg_match_all('/<(script src|link rel="stylesheet")[^>]*>/', $html, $matches);
        $this->assertCount(7, $matches[0]);
        foreach ($matches[0] as $tag) {
            $this->assertStringContainsString('data-simgen-lib', $tag);
        }
    }
}
