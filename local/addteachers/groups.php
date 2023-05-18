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
 * @package    local_addteachers
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/group/lib.php';

global $USER, $PAGE;

require_login();

$PAGE->set_url(new moodle_url('/local/addteachers/groups.php'));
$PAGE->set_title(get_string('localuserheader', 'local_addteachers'));
$PAGE->requires->js_call_amd('local_addteachers/modal_addgroup');

$templatecontext = (object) [
    'headertext' => get_string('localgroupsheader', 'local_addteachers'),
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

$values = optional_param('group', '', PARAM_ALPHANUMEXT);
$courseid = (int) explode('-', $values)[0];
$groupid = (int) explode('-', $values)[1];

// TODO: change it to the config field
$teacherCourses = [
    (object) ['courseID' => 14, 'relatedID' => 13],
    (object) ['courseID' => 16, 'relatedID' => 15],
    (object) ['courseID' => 18, 'relatedID' => 17],
];

// Courses on the test platform
// $teacherCourses = [
//     (object) ['courseID' => 11, 'relatedID' => 13],
//     (object) ['courseID' => 6, 'relatedID' => 12],
// ];

// We need new structure to search relatedIDs
foreach ($teacherCourses as $key => $value) {
    $teacherCourses[$key] = $value->relatedID;
}

$groupname = '';

$courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
$groups = groups_get_my_groups();
$groupings = (object) [];
$classes = [];

// Find schools from 'dziennik'
foreach ($groups as &$group) {
    $contextCourse = context_course::instance($group->courseid);
    $roles = get_user_roles($contextCourse, $USER->id, true);
    $role = key($roles);
    $group->rolename = $roles[$role]->shortname;

    if ($group->courseid == '10') {
        $groupings = $group;
    } elseif ($group->rolename == 'teacherkeg') {
        $classes[] = $group;
    }
}

$templatecontext->groups = [];

foreach ($classes as &$class) {
    if (!in_array((int) $class->courseid, $teacherCourses)) {
        $teachers = groups_get_members($class->id, $fields = 'u.*', $sort = 'lastname ASC');
        $course = get_course($class->courseid);
        $class->groupname = $course->shortname . ' - ' . $class->name;

        $contextCourse = context_course::instance($class->courseid);

        $class->teachers = [];
        $class->students = [];

        foreach ($teachers as $teacher) {
            profile_load_data($teacher);
            $roles = get_user_roles($contextCourse, $teacher->id, true);
            $role = key($roles);

            if ($roles[$role]->shortname == 'teacher' and !findObjectById($class->teachers, $teacher->id)) {
                $class->teachers[] = $teacher;
            } elseif ($roles[$role]->shortname == 'student' and !findObjectById($class->students, $teacher->id)) {
                $class->students[] = $teacher;
            }
        }

        $templatecontext->groups[] = $class;
    }
}

foreach ($courses as $key => &$course) {
    $course->id = $course->id . '-' . intval(groups_get_grouping_by_name($course->id, $groupings->name));
    if ($course->category == '4' or in_array((int) $course->id, $teacherCourses)) {
        unset($courses[$key]);
    }
}

$courses = array_values($courses);

$PAGE->requires->js_call_amd('local_addteachers/configgroup', 'init', array(get_string('group', 'local_addteachers'), get_string('edit', 'local_addteachers'), $courses));

$templatecontext->anyGroups = count($templatecontext->groups) > 0 ? true : false;

function findObjectById($array, $id)
{
    foreach ($array as $element) {
        if ($id == $element->id) {
            return $element;
        }
    }

    return false;
}

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

echo '<h1>' . $templatecontext->headertext . '</h1><p>' . $groupings->name . '</p>';

echo $OUTPUT->render_from_template('local_addteachers/groups', $templatecontext);

echo $OUTPUT->footer();
