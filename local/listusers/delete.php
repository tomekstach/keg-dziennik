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
 * @package    local_listusers
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->dirroot . '/local/addusers/vendor/llagerlof/moodlerest/MoodleRest.php';

global $USER, $PAGE;

require_login();

if (!isguestuser()) {
    $token = get_config('local_addcoordinator', 'apitoken');
    $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';
    $MoodleRest = new MoodleRest($baseurl, $token);
    //$MoodleRest->setDebug();

    $courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups = groups_get_my_groups();

    $IDs = optional_param('id', '', PARAM_ALPHANUMEXT);

    $course = (object) ['id' => explode('-', $IDs)[0], 'access' => false];
    $group = (object) ['id' => explode('-', $IDs)[1], 'access' => false];
    $student = (object) ['id' => explode('-', $IDs)[2], 'exists' => false];

    foreach ($courses as $item) {
        if ($item->id == $course->id) {
            $contextCourse = context_course::instance($course->id);
            $roles = get_user_roles($contextCourse, $USER->id, true);
            $role = key($roles);
            $rolename = $roles[$role]->shortname;

            if ($rolename == 'teacher') {
                $course->access = true;
                $students = get_role_users(5, $contextCourse);
            }
        }
    }

    if ($course->access === false) {
        echo '{"message": "Error: You do not have access to this course!", "error": true}';
    } else {
        foreach ($groups as $item) {
            if ($item->courseid == $course->id and $item->id == $group->id) {
                $group->access = true;
            }
        }

        if ($group->access === false) {
            echo '{"message": "Error: You do not have access to this group!", "error": true}';
        } else {
            if (!isset($students[$student->id])) {
                echo '{"message": "Error: This student is not assigned to this group!", "error": true}';
            } else {
                $enrolments[] = [
                    'roleid' => '5',
                    'userid' => (int) $student->id,
                    'courseid' => $course->id,
                ];
                $members[] = [
                    'userid' => (int) $student->id,
                    'groupid' => $group->id,
                ];
                // Run API methods
                $response = $MoodleRest->request('enrol_manual_unenrol_users', array('enrolments' => $enrolments));
                $response = $MoodleRest->request('core_group_delete_group_members', array('members' => $members));
            }
        }
    }
}