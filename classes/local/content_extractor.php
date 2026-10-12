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

use context_module;
use stored_file;
use stdClass;

/**
 * Extracts plain text from the content entered and uploaded by the teacher.
 *
 * Supported: txt, md, csv, json, html, docx, pptx, odt, odp and pdf (pdf needs pdftotext or Ghostscript on the server).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_extractor {

    /** @var int Default maximum number of characters sent to the AI. */
    const DEFAULT_MAX_CHARS = 30000;

    /**
     * Returns the complete content text (editor + files), truncated to the configured maximum.
     *
     * @param stdClass $simgen
     * @param context_module $context
     * @return string
     */
    public static function extract(stdClass $simgen, context_module $context): string {
        $parts = [];

        $editortext = trim(content_to_text((string)($simgen->content ?? ''), (int)($simgen->contentformat ?? FORMAT_HTML)));
        if ($editortext !== '') {
            $parts[] = $editortext;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_simgen', 'content', 0, 'filename', false);
        foreach ($files as $file) {
            $text = trim(self::extract_file($file));
            if ($text === '') {
                $text = '[' . get_string('extract_failed', 'mod_simgen') . ']';
            }
            $parts[] = '### ' . $file->get_filename() . "\n" . $text;
        }

        $text = self::normalise(implode("\n\n", $parts));

        $max = (int)get_config('mod_simgen', 'maxcontentchars');
        if ($max <= 0) {
            $max = self::DEFAULT_MAX_CHARS;
        }
        if (\core_text::strlen($text) > $max) {
            $text = \core_text::substr($text, 0, $max) . "\n[… gekürzt: Das Material ist länger als " . $max . ' Zeichen.]';
        }
        return $text;
    }

    /**
     * Extracts the text of a single stored file.
     *
     * @param stored_file $file
     * @return string
     */
    public static function extract_file(stored_file $file): string {
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        try {
            switch ($extension) {
                case 'txt':
                case 'md':
                case 'csv':
                case 'json':
                    return self::to_utf8($file->get_content());
                case 'html':
                case 'htm':
                    return html_to_text(self::to_utf8($file->get_content()), 0, false);
                case 'docx':
                    return self::extract_zip_xml($file, '#^word/document\.xml$#', 'w:p');
                case 'pptx':
                    return self::extract_zip_xml($file, '#^ppt/slides/slide(\d+)\.xml$#', 'a:p', 'Folie');
                case 'odt':
                case 'odp':
                    return self::extract_zip_xml($file, '#^content\.xml$#', 'text:p');
                case 'pdf':
                    return self::extract_pdf($file);
                default:
                    return '';
            }
        } catch (\Throwable $e) {
            debugging('mod_simgen: could not extract ' . $file->get_filename() . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '';
        }
    }

    /**
     * Extracts text from XML entries of an office (zip) document.
     *
     * @param stored_file $file
     * @param string $entrypattern regex matching the relevant zip entries (first group = sort number)
     * @param string $paragraphtag tag that ends a paragraph
     * @param string|null $label label for each entry (e.g. "Folie") or null
     * @return string
     */
    private static function extract_zip_xml(stored_file $file, string $entrypattern, string $paragraphtag,
            ?string $label = null): string {
        if (!class_exists('\ZipArchive')) {
            return '';
        }
        $tmp = $file->copy_content_to_temp('mod_simgen');
        if (!$tmp) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return '';
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match($entrypattern, $name, $matches)) {
                $entries[$name] = isset($matches[1]) ? (int)$matches[1] : 0;
            }
        }
        asort($entries, SORT_NUMERIC);

        $out = [];
        foreach ($entries as $name => $number) {
            $xml = $zip->getFromName($name);
            if ($xml === false) {
                continue;
            }
            $xml = str_replace(['</' . $paragraphtag . '>', '<w:tab/>', '<w:br/>', '<text:line-break/>'],
                ["\n", "\t", "\n", "\n"], $xml);
            $text = trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
            if ($text === '') {
                continue;
            }
            $out[] = $label ? "--- {$label} {$number} ---\n{$text}" : $text;
        }
        $zip->close();
        @unlink($tmp);
        return implode("\n\n", $out);
    }

    /**
     * Extracts text from a PDF using pdftotext (if configured) or Ghostscript ($CFG->pathtogs).
     *
     * @param stored_file $file
     * @return string
     */
    private static function extract_pdf(stored_file $file): string {
        global $CFG;

        if (!function_exists('exec')) {
            return '';
        }
        $tmp = $file->copy_content_to_temp('mod_simgen');
        if (!$tmp) {
            return '';
        }

        $text = '';
        $pdftotext = trim((string)get_config('mod_simgen', 'pathtopdftotext'));
        if ($pdftotext !== '' && is_executable($pdftotext)) {
            $cmd = escapeshellarg($pdftotext) . ' -enc UTF-8 -layout ' . escapeshellarg($tmp) . ' -';
            $text = self::run($cmd);
        }
        if (trim($text) === '' && !empty($CFG->pathtogs) && is_executable($CFG->pathtogs)) {
            $cmd = escapeshellarg($CFG->pathtogs) . ' -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=txtwrite -sOutputFile=- '
                . escapeshellarg($tmp);
            $text = self::run($cmd);
        }
        @unlink($tmp);
        return self::to_utf8($text);
    }

    /**
     * Runs a shell command and returns its output.
     *
     * @param string $cmd
     * @return string
     */
    private static function run(string $cmd): string {
        $output = [];
        $code = 0;
        exec($cmd . (DIRECTORY_SEPARATOR === '\\' ? ' 2>NUL' : ' 2>/dev/null'), $output, $code);
        return $code === 0 ? implode("\n", $output) : '';
    }

    /**
     * Converts a string to UTF-8 if necessary.
     *
     * @param string $text
     * @return string
     */
    private static function to_utf8(string $text): string {
        // Remove UTF-8 BOM.
        if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) {
            $text = substr($text, 3);
        }
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = \core_text::convert($text, 'windows-1252', 'utf-8');
        }
        return $text;
    }

    /**
     * Normalises whitespace to save tokens.
     *
     * @param string $text
     * @return string
     */
    private static function normalise(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+\n/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }
}
