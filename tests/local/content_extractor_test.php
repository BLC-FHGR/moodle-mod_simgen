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
 * Tests of the content extractor.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content_extractor::class)]
final class content_extractor_test extends \advanced_testcase {

    /**
     * Stores a minimal PowerPoint file.
     *
     * @param array $files zip entry => content
     * @return \stored_file
     */
    private function create_pptx(array $files): \stored_file {
        $path = make_request_directory() . '/test.pptx';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return get_file_storage()->create_file_from_pathname([
            'contextid' => \context_system::instance()->id,
            'component' => 'mod_simgen',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test.pptx',
        ], $path);
    }

    /**
     * XML of a slide with one paragraph.
     *
     * @param string $text
     * @return string
     */
    private function slide(string $text): string {
        return '<p:sld><p:cSld><p:spTree><p:sp><p:txBody><a:p><a:r><a:t>' . $text
            . '</a:t></a:r></a:p></p:txBody></p:sp></p:spTree></p:cSld></p:sld>';
    }

    /**
     * Slides come in the order of the presentation, not of the file names.
     */
    public function test_pptx_slide_order(): void {
        $this->resetAfterTest();
        $file = $this->create_pptx([
            'ppt/slides/slide1.xml' => $this->slide('Created first'),
            'ppt/slides/slide2.xml' => $this->slide('Created second'),
            'ppt/slides/slide3.xml' => $this->slide('Not part of the presentation'),
            'ppt/presentation.xml' => '<p:presentation><p:sldIdLst><p:sldId id="257" r:id="rId3"/>'
                . '<p:sldId id="256" r:id="rId2"/></p:sldIdLst></p:presentation>',
            'ppt/_rels/presentation.xml.rels' => '<Relationships>'
                . '<Relationship Id="rId2" Type="x/slide" Target="slides/slide1.xml"/>'
                . '<Relationship Target="/ppt/slides/slide2.xml" Type="x/slide" Id="rId3"/></Relationships>',
        ]);

        $text = content_extractor::extract_file($file);

        $this->assertSame("--- Folie 1 ---\nCreated second\n\n--- Folie 2 ---\nCreated first", $text);
    }

    /**
     * Without presentation.xml the slides are sorted by their numbers.
     */
    public function test_pptx_without_presentation(): void {
        $this->resetAfterTest();
        $file = $this->create_pptx([
            'ppt/slides/slide10.xml' => $this->slide('Ten'),
            'ppt/slides/slide2.xml' => $this->slide('Two'),
        ]);

        $text = content_extractor::extract_file($file);

        $this->assertSame("--- Folie 2 ---\nTwo\n\n--- Folie 10 ---\nTen", $text);
    }
}
