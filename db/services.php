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

/**
 * External functions of mod_simgen (used by the player via AJAX).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_simgen_get_state' => [
        'classname' => \mod_simgen\external\get_state::class,
        'description' => 'Returns the saved progress of the current user.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/simgen:view',
    ],
    'mod_simgen_save_state' => [
        'classname' => \mod_simgen\external\save_state::class,
        'description' => 'Saves the progress (and optionally the completion) of the current user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:savestate',
    ],
    'mod_simgen_reset_state' => [
        'classname' => \mod_simgen\external\reset_state::class,
        'description' => 'Deletes the saved progress of the current user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:savestate',
    ],
    'mod_simgen_get_conversation' => [
        'classname' => \mod_simgen\external\get_conversation::class,
        'description' => 'Returns the conversation of the current user with an AI persona.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/simgen:converse',
    ],
    'mod_simgen_converse' => [
        'classname' => \mod_simgen\external\converse::class,
        'description' => 'Sends a message of the current user to an AI persona and returns its answer.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:converse',
    ],
    'mod_simgen_reset_conversation' => [
        'classname' => \mod_simgen\external\reset_conversation::class,
        'description' => 'Deletes the conversation of the current user with an AI persona.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:converse',
    ],
    'mod_simgen_end_conversation' => [
        'classname' => \mod_simgen\external\end_conversation::class,
        'description' => 'Ends the conversation of the current user with an AI persona.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:converse',
    ],
    'mod_simgen_get_status' => [
        'classname' => \mod_simgen\external\get_status::class,
        'description' => 'Returns the generation status of a simulation.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/simgen:manage',
    ],
    'mod_simgen_report_check' => [
        'classname' => \mod_simgen\external\report_check::class,
        'description' => 'Stores the result of the start-up check of a version and its compiled code.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/simgen:manage',
    ],
];
