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
 * Tests of the response parser.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(response_parser::class)]
final class response_parser_test extends \advanced_testcase {

    /**
     * An empty CSS block must not swallow the following JSX block.
     */
    public function test_empty_block(): void {
        $parsed = response_parser::parse("```css\n```\n\n```jsx\nfunction App() { return null; }\n```\n");
        $this->assertSame('react', $parsed['codetype']);
        $this->assertSame('function App() { return null; }', $parsed['sourcecode']);
        $this->assertSame('', $parsed['css']);

        $this->assertSame([['lang' => 'css', 'code' => '']], response_parser::extract_blocks("```css\n```"));
    }

    /**
     * A closing fence may be longer than the opening one and is not part of the code.
     */
    public function test_longer_closing_fence(): void {
        $parsed = response_parser::parse("```jsx\nfunction App() {}\n````\n");
        $this->assertSame('function App() {}', $parsed['sourcecode']);
    }

    /**
     * Schedule, CSS and JSX in raw Markdown.
     */
    public function test_raw_markdown(): void {
        $parsed = response_parser::parse("Plan:\n```json\n{\"plan\": [{\"phase\": \"Einstieg\", \"activity\": \"Lesen\", "
            . "\"minutes\": 5}]}\n```\n\n```css\n.app { color: red; }\n```\n\n~~~jsx\nfunction App() {}\n~~~\n");
        $this->assertSame('.app { color: red; }', $parsed['css']);
        $this->assertSame('function App() {}', $parsed['sourcecode']);
        $this->assertNotSame('', $parsed['timeplan']);
    }

    /**
     * Usage examples and repeated versions of the component are not joined with the code.
     */
    public function test_several_js_blocks(): void {
        $parsed = response_parser::parse("Beispiel:\n```jsx\n<Formula tex=\"x\" />\n```\n\n"
            . "```jsx\nfunction App() { return null; }\n```\n");
        $this->assertSame('function App() { return null; }', $parsed['sourcecode']);

        // Helper components in a separate block stay.
        $parsed = response_parser::parse("```jsx\nfunction Card() { return null; }\n```\n\n"
            . "```jsx\nfunction App() { return <Card />; }\n```\n");
        $this->assertSame("function Card() { return null; }\n\nfunction App() { return <Card />; }", $parsed['sourcecode']);

        // A corrected second version replaces the first one.
        $parsed = response_parser::parse("```jsx\nfunction App() { return 1; }\n```\nKorrigiert:\n"
            . "```jsx\nfunction Helper() {}\nfunction App() { return 2; }\n```\n");
        $this->assertSame("function Helper() {}\nfunction App() { return 2; }", $parsed['sourcecode']);
    }

    /**
     * Code blocks in the HTML of local_ai_manager (escaped code).
     */
    public function test_html_blocks(): void {
        $content = '<p>Code:</p><pre class="language-css"><code></code></pre>'
            . '<pre class="language-jsx"><code>function App() { return &lt;div title=&quot;a &amp;amp; b&quot;&gt;'
            . '&#39;x&#39;&lt;/div&gt;; }</code></pre>';
        $parsed = response_parser::parse($content);
        $this->assertSame('function App() { return <div title="a &amp; b">\'x\'</div>; }', $parsed['sourcecode']);
        $this->assertSame('', $parsed['css']);
    }
}
