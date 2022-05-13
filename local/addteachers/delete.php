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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/local/addusers/vendor/llagerlof/moodlerest/MoodleRest.php');

global $USER, $PAGE;

require_login();

if (!isguestuser()) {
  $tokenurl       = $CFG->wwwroot . '/login/token.php?username=wiktor&password=!53W7qbec&service=kegmanager';
  $tokenresponse  = file_get_contents($tokenurl);
  $tokenobject    = json_decode($tokenresponse);

  if (!empty($tokenobject->error)) {
    \core\notification::add($tokenobject->error, \core\output\notification::NOTIFY_ERROR);
  } else {
    $baseurl      = $CFG->wwwroot . '/webservice/rest/server.php';
    $MoodleRest   = new MoodleRest($baseurl, $tokenobject->token);
    //$MoodleRest->setDebug();

    $courses  = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups   = groups_get_my_groups();

    $IDs      = optional_param('id', '', PARAM_ALPHANUMEXT);

    $course   = (object) ['id' => explode('-', $IDs)[0], 'access' => false];
    $group    = (object) ['id' => explode('-', $IDs)[1], 'access' => false];
    $student  = (object) ['id' => explode('-', $IDs)[2], 'exists' => false];

    foreach ($courses as $item) {
      if ($item->id == $course->id) {
        $contextCourse  = context_course::instance($course->id);
        $roles          = get_user_roles($contextCourse, $USER->id, true);
        $role           = key($roles);
        $rolename       = $roles[$role]->shortname;

        if ($rolename == 'teacherkeg') {
          $course->access = true;
          $students = get_role_users(4, $contextCourse);
        }
      }
    }

    if ($course->access === false) {
      echo '{"message": "Error: Nie masz uprawnień do tego modułu!", "error": true}';
    } else {
      foreach ($groups as $item) {
        if ($item->courseid == $course->id and $item->id == $group->id) {
          $group->access = true;
        }
      }

      if ($group->access === false) {
        echo '{"message": "Error: Nie masz uprawnień do tej klasy!", "error": true}';
      } else {
        if (!isset($students[$student->id])) {
          echo '{"message": "Error: Ten nauczyciel nie jest przypisany do tej klasy!", "error": true}';
        } else {
          $members[] = [
            'userid'    => (int) $student->id,
            'groupid'   => $group->id
          ];
          // Run API methods
          $response = $MoodleRest->request('core_group_delete_group_members', array('members' => $members));
        }
      }
    }
  }
}
