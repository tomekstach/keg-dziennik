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

    $groups   = groups_get_my_groups();

    $group    = (object) ['id' => (int) optional_param('id', '', PARAM_ALPHANUMEXT), 'access' => false];

    foreach ($groups as $item) {
      if ($item->id == $group->id) {
        $group->access = true;
      }
    }

    if ($group->access === false) {
      echo '{"message": "Error: You do not have access to this group!", "error": true}';
    } else {
      $groups[] = [$group->id];
      // Get students
      $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');

      foreach ($students as $student) {
        $members[] = [
          'userid'    => (int) $student->id,
          'groupid'   => $group->id
        ];
      }
      // Run API methods
      // Remove students from this group
      $response = $MoodleRest->request('core_group_delete_group_members', array('members' => $members));
      // Delete group
      $response = $MoodleRest->request('core_group_delete_groups', array('groupids' => $groups));
    }
  }
}