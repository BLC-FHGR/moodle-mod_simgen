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
use stdClass;

/**
 * Fills the briefing of the teacher into the prompt templates.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_builder {

    /** @var string Placeholder for the technical rules. */
    const PLACEHOLDER_TECHNICAL = '{{TECHNICAL_RULES}}';

    /**
     * Prompt for generating a simulation from scratch.
     *
     * @param stdClass $simgen instance record
     * @param context_module $context
     * @return string
     */
    public static function build_generate_prompt(stdClass $simgen, context_module $context): string {
        $template = self::get_template('prompt_generate', prompt_templates::GENERATE);
        $map = self::briefing_placeholders($simgen, $context);
        $map[self::PLACEHOLDER_TECHNICAL] = self::technical_rules((bool)$simgen->savestate, 'react',
            self::is_conversation($simgen));
        return self::fill($template, $map);
    }

    /**
     * Prompt for revising an existing version.
     *
     * @param stdClass $simgen instance record
     * @param context_module $context
     * @param stdClass $version version to revise
     * @param string $changerequest change request or error message of the teacher
     * @return string
     */
    public static function build_revision_prompt(stdClass $simgen, context_module $context, stdClass $version,
            string $changerequest): string {
        $template = self::get_template('prompt_revise', prompt_templates::REVISE);
        $map = self::briefing_placeholders($simgen, $context);
        $map['{{CHANGE_REQUEST}}'] = trim($changerequest) !== '' ? trim($changerequest)
            : 'Verbessere die Simulation didaktisch und technisch, ohne ihren Charakter zu verändern.';
        $conversation = self::is_conversation($simgen, $version);
        if ($version->codetype === 'html') {
            $map['{{CURRENT_CODE}}'] = "```html\n" . $version->sourcecode . "\n```";
        } else {
            $current = "```css\n" . ($version->css ?? '') . "\n```\n\n";
            if ($conversation) {
                $personas = ['personas' => array_values(conversation::from_version($version))];
                $current .= "```json\n" . json_encode($personas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES) . "\n```\n\n";
            }
            $map['{{CURRENT_CODE}}'] = $current . "```jsx\n" . $version->sourcecode . "\n```";
        }
        $map[self::PLACEHOLDER_TECHNICAL] = self::technical_rules((bool)$simgen->savestate, $version->codetype,
            $conversation);
        return self::fill($template, $map);
    }

    /**
     * Whether the simulation uses live conversations with AI personas.
     *
     * @param stdClass $simgen
     * @param stdClass|null $version version to revise (a version with personas stays a conversation simulation)
     * @return bool
     */
    public static function is_conversation(stdClass $simgen, ?stdClass $version = null): bool {
        if ($version && $version->codetype === 'html') {
            return false;
        }
        return $simgen->simtype === 'conversation' || ($version && !empty(conversation::from_version($version)));
    }

    /**
     * Maximum working time in minutes (admin setting).
     *
     * @return int
     */
    public static function max_duration(): int {
        $value = (int)get_config('mod_simgen', 'maxduration');
        return $value > 0 ? $value : 60;
    }

    /**
     * The (non editable) runtime contract.
     *
     * @param bool $savestate whether progress is persisted
     * @param string $codetype react or html
     * @param bool $conversation live conversations with AI personas
     * @return string
     */
    public static function technical_rules(bool $savestate, string $codetype, bool $conversation = false): string {
        if ($codetype === 'html') {
            return prompt_templates::TECHNICAL_HTML;
        }
        return strtr(prompt_templates::TECHNICAL_REACT, [
            '{{SAVESTATE_RULES}}' => $savestate ? prompt_templates::SAVESTATE_ON : prompt_templates::SAVESTATE_OFF,
            '{{CONVERSATION_RULES}}' => $conversation ? prompt_templates::CONVERSATION_RULES : '',
            '{{OUTPUT_FORMAT}}' => $conversation ? prompt_templates::OUTPUT_REACT_CONVERSATION
                : prompt_templates::OUTPUT_REACT,
        ]);
    }

    /**
     * Placeholder values built from the briefing.
     *
     * @param stdClass $simgen
     * @param context_module $context
     * @return array placeholder => value
     */
    public static function briefing_placeholders(stdClass $simgen, context_module $context): array {
        $none = '(keine Angabe)';
        $minutes = min((int)$simgen->durationmin, self::max_duration());
        $duration = $minutes > 0 ? ($minutes . ' Minuten') : $none;

        $description = trim(content_to_text((string)($simgen->intro ?? ''), (int)($simgen->introformat ?? FORMAT_HTML)));

        return [
            '{{TITLE}}' => self::value($simgen->name ?? '', $none),
            '{{DESCRIPTION}}' => self::value($description, $none),
            '{{SUBJECT}}' => self::value($simgen->subject ?? '', $none),
            '{{TARGETGROUP}}' => self::value($simgen->targetgroup ?? '', $none),
            '{{PRIORKNOWLEDGE}}' => self::value($simgen->priorknowledge ?? '', $none),
            '{{DIFFICULTY}}' => options::DIFFICULTIES[$simgen->difficulty] ?? $none,
            '{{DURATION}}' => $duration,
            '{{SOCIALFORM}}' => options::SOCIALFORMS[$simgen->socialform] ?? $none,
            '{{LANGUAGE}}' => options::LANGUAGES[$simgen->simlanguage] ?? 'Deutsch',
            '{{SIMTYPE}}' => options::SIMTYPES[$simgen->simtype] ?? options::SIMTYPES['auto'],
            '{{FEEDBACKMODE}}' => options::FEEDBACKMODES[$simgen->feedbackmode] ?? options::FEEDBACKMODES['immediate'],
            '{{LEARNINGOUTCOMES}}' => self::value($simgen->learningoutcomes ?? '', $none),
            '{{REQUIREMENTS}}' => self::value($simgen->requirements ?? '', $none),
            '{{CONTENT}}' => self::value(content_extractor::extract($simgen, $context), '(keine Inhalte hinterlegt)'),
        ];
    }

    /**
     * Returns the admin template or the default template.
     *
     * @param string $configname
     * @param string $default
     * @return string
     */
    private static function get_template(string $configname, string $default): string {
        $template = trim((string)get_config('mod_simgen', $configname));
        if ($template === '') {
            $template = $default;
        }
        // The player only works if the runtime contract is part of the prompt.
        if (strpos($template, self::PLACEHOLDER_TECHNICAL) === false) {
            $template .= "\n\n" . self::PLACEHOLDER_TECHNICAL;
        }
        return $template;
    }

    /**
     * Replaces all placeholders in a single pass (so user input cannot inject placeholders).
     *
     * @param string $template
     * @param array $map
     * @return string
     */
    private static function fill(string $template, array $map): string {
        return strtr($template, $map);
    }

    /**
     * Trimmed value or fallback.
     *
     * @param string $value
     * @param string $fallback
     * @return string
     */
    private static function value(string $value, string $fallback): string {
        $value = trim($value);
        return $value === '' ? $fallback : $value;
    }
}
