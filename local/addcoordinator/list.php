<?php
// This file is part of Moodle Add Users Plugin
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Readme file for local customisations
 *
 * @package    local_addcoordinator
 * @copyright  2022 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/group/lib.php';

global $USER, $PAGE;

require_login();

$PAGE->set_url(new moodle_url('/local/addcoordinator/list.php'));
$PAGE->set_title(get_string('localuserheader', 'local_addcoordinator'));
$PAGE->requires->js_call_amd('local_addcoordinator/modal_edit');

$templatecontext = (object) [
    'headertext' => get_string('localuserheader', 'local_addcoordinator'),
];

if (isguestuser()) { // Force them to see system default, no editing allowed
    // If guests are not allowed my moodle, send them to front page.
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }

    $userid = null;
    $USER->editing = $edit = 0; // Just in case
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages'); // unlikely :)
} else { // We are trying to view or edit our own My Moodle page
    $userid = $USER->id; // Owner of the page
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
}

$allSchools = groups_get_all_groups('10');
$schools = [];

foreach ($allSchools as $school) {
    $users = groups_get_members($school->id, $fields = 'u.*', $sort = 'lastname ASC');
    $contextCourse = context_course::instance('10');

    foreach ($users as $user) {
        profile_load_data($user);
        $roles = get_user_roles($contextCourse, $user->id, true);
        $role = key($roles);
        if ($roles[$role]->shortname === 'teacherkeg') {
            if ($user->lastaccess > 0) {
                $user->lastaccess = date('Y-m-d H:i:s', $user->lastaccess);
            } else {
                $user->lastaccess = get_string('never', 'local_addcoordinator');
            }
            $school->coordinator = $user;
            $schools[] = $school;
        }
        unset($roles);
        unset($role);
    }
}

$templatecontext->schools = [];
$templatecontext->schools = $schools;

$PAGE->requires->js_call_amd('local_addcoordinator/config', 'init', array(get_string('editcoordinator', 'local_addcoordinator')));

$templatecontext->anyCoordinators = count($templatecontext->schools) > 0 ? true : false;

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

echo '<h1>' . $templatecontext->headertext . '</h1><p>' . $groupings->name . '</p>';

echo $OUTPUT->render_from_template('local_addcoordinator/list', $templatecontext);

echo $OUTPUT->footer();
