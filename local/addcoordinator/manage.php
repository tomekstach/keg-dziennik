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
require_once $CFG->dirroot . '/local/addcoordinator/classes/form/edit.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->dirroot . '/user/lib.php';

global $USER, $DB;

require_login();

$PAGE->set_url(new moodle_url('/local/addcoordinator/manage.php'));
$PAGE->set_title(get_string('localcoordinatorheader', 'local_addcoordinator'));

$templatecontext = (object) [
    'texttodisplay' => get_string('localcoordinatortext', 'local_addcoordinator'),
    'headertext' => get_string('localcoordinatorheader', 'local_addcoordinator'),
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

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

if (!function_exists('clearString')) {
    function clearString($string)
    {
        return addslashes(stripslashes(strip_tags(trim($string))));
    }
}

$uform = new edit();

$templatecontext->anyTeachers = false;
$templatecontext->teachers = [];

//Form processing and displaying is done here
if ($uform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
    \core\notification::add(get_string('formwascleared', 'local_addcoordinator'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->render_from_template('local_addcoordinator/manage', $templatecontext);
    $uform->display();
} else if ($fromform = $uform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    //print_r($fromform);
    if ((int) $fromform->course == 0) {
        \core\notification::add(get_string('missingcourse', 'local_addcoordinator'), \core\output\notification::NOTIFY_ERROR);
        echo $OUTPUT->render_from_template('local_addcoordinator/manage', $templatecontext);
        $uform->display();
    } else {
        try {
            $groups = groups_get_my_groups();
            //print_r($groups);

            if (groups_get_grouping_by_name($fromform->course, $fromform->schoolname) !== false) {
                throw new Exception('Podana szkoła już istnieje w wybranym kursie!');
            }

            // Add group in the selected course for the school (main group - groups_create_grouping($data, $editoroptions=null))
            $schoolData = (object) [
                'name' => clearString($fromform->schoolname),
                'courseid' => (int) $fromform->course,
            ];
            $schoolID = groups_create_grouping($schoolData);

            // Add group in the selected course for the class (standard group - groups_create_group($data, $editform=false, $editoroptions=null))
            $classData = (object) [
                'name' => clearString($fromform->classname),
                'courseid' => (int) $fromform->course,
            ];
            $classID = groups_create_group($classData);

            // Assigne class group to the school one - groups_assign_grouping($groupingid, $groupid)
            groups_assign_grouping($schoolID, $classID);

            // Add group in the 'Dziennik lekcji' for the school (standard group - groups_create_group($data, $editform=false, $editoroptions=null))
            $schoolLessonDiaryData = (object) [
                'name' => clearString($fromform->schoolname),
                'courseid' => '10',
            ];
            $schoolLessonDiaryID = groups_create_group($schoolLessonDiaryData);

            // Create user's data
            $user = [
                // username = email
                'username' => clearString($fromform->email),
                'password' => clearString($fromform->password),
                'firstname' => clearString($fromform->firstname),
                'lastname' => clearString($fromform->lastname),
                'email' => clearString($fromform->email),
                'lang' => 'pl',
                'preferences' => [
                    0 => [
                        'type' => 'auth_forcepasswordchange',
                        'value' => 1,
                    ],
                ],
            ];

            $userID = (int) user_create_user($user);

            enrol_try_internal_enrol($fromform->course, $userID, 9);

            enrol_try_internal_enrol(10, $userID, 9);

            groups_add_member($classID, $userID);

            groups_add_member($schoolLessonDiaryID, $userID);

            \core\notification::add(get_string('coordinatorwasadded', 'local_addcoordinator'), \core\output\notification::NOTIFY_SUCCESS);
            echo $OUTPUT->render_from_template('local_addcoordinator/manage', $templatecontext);
        } catch (Exception $th) {
            \core\notification::add($th->getMessage(), \core\output\notification::NOTIFY_ERROR);
            echo $OUTPUT->render_from_template('local_addcoordinator/manage', $templatecontext);
            $uform->display();
        }
    }
} else {
    $uform->display();
}

echo $OUTPUT->footer();
