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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/listusers/classes/form/filter.php');
require_once($CFG->dirroot . '/group/lib.php');

global $USER, $PAGE;

require_login();

$PAGE->set_url(new moodle_url('/local/listusers/manage.php'));
$PAGE->set_title(get_string('localuserheader', 'local_listusers'));
$PAGE->requires->js_call_amd('local_listusers/modal_edit');
$PAGE->requires->js_call_amd('local_listusers/config', 'init', array(get_string('nrdziennika', 'local_listusers'), get_string('nrdziennikafull', 'local_listusers'), get_string('edit', 'local_listusers')));

$templatecontext = (object) [
  'texttodisplay' => get_string('localusertext', 'local_listusers'),
  'headertext' => get_string('localuserheader', 'local_listusers')
];

if (isguestuser()) {  // Force them to see system default, no editing allowed
  // If guests are not allowed my moodle, send them to front page.
  if (empty($CFG->allowguestmymoodle)) {
    redirect(new moodle_url('/', array('redirect' => 0)));
  }

  $userid = null;
  $USER->editing = $edit = 0;  // Just in case
  $context = context_system::instance();
  $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
} else {        // We are trying to view or edit our own My Moodle page
  $userid = $USER->id;  // Owner of the page
  $context = context_user::instance($USER->id);
  $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
}

$values     = optional_param('group', '', PARAM_ALPHANUMEXT);
$courseid   = (int) explode('-', $values)[0];
$groupid    = (int) explode('-', $values)[1];

$groupname  = '';

$choices = [];
$choices[0] = get_string('studentsgroup', 'local_listusers');
$courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
$groups = groups_get_my_groups();
$groupings = [];
$parentGroups = [];

// Find schools from 'dziennik'
foreach ($groups as &$group) {
  $contextCourse = context_course::instance($group->courseid);
  $roles = get_user_roles($contextCourse, $USER->id, true);
  $role = key($roles);
  $group->rolename = $roles[$role]->shortname;

  if ($group->courseid == '10') {
    $groupings[] = $group;
  }
}

// Find schools in courses where user has role teacherkeg and it is not 'dziennik'
if (count($groupings) > 0) {
  foreach ($courses as $course) {
    if ($course->category != '4') {
      $contextCourse = context_course::instance($course->id);
      $roles = get_user_roles($contextCourse, $USER->id, true);
      $role = key($roles);
      $rolename = $roles[$role]->shortname;

      if ($rolename == 'teacher') {
        foreach ($groupings as $group) {
          if ($courseid == 0) {
            $courseid = $course->id;
          }
          $parentGroup = new \stdClass;
          $parentGroup->courseid = $course->id;
          $parentGroup->coursename = $course->shortname;
          $parentGroup->groupingid = intval(groups_get_grouping_by_name($course->id, $group->name));
          $parentGroup->schoolid   = intval($group->id);

          if ($parentGroup->groupingid > 0) {
            $parentGroup->groupingname = groups_get_grouping_name($parentGroup->groupingid);
            $parentGroups[] = $parentGroup;
          }
        }
      }
    }
  }

  foreach ($parentGroups as &$parentGroup) {
    $parentGroup->groups = groups_get_all_groups($parentGroup->courseid, 0, $parentGroup->groupingid, 'g.*');
    foreach ($parentGroup->groups as $group) {
      if ($groups[$group->id]->rolename == 'teacher') {
        if ($groupid == 0) {
          $groupid    = $group->id;
          $groupname  = $parentGroup->coursename . ', ' . $parentGroup->groupingname . ', ' . $group->name;
        } elseif ($group->id == $groupid) {
          $groupname  = $parentGroup->coursename . ', ' . $parentGroup->groupingname . ', ' . $group->name;
        }
        $choices[$parentGroup->courseid . '-' . $group->id] = $parentGroup->coursename . ', ' . $parentGroup->groupingname . ', ' . $group->name;
      }
    }
  }
}

$templatecontext->courseid  = $courseid;
$templatecontext->groupid   = $groupid;
$students = groups_get_members($groupid, $fields = 'u.*', $sort = 'lastname ASC');

$templatecontext->students = [];

$contextCourse = context_course::instance($courseid);

foreach ($students as $student) {
  $userStudent  = profile_user_record($student->id);
  $roles        = get_user_roles($contextCourse, $student->id, true);
  $role         = key($roles);

  if ($roles[$role]->shortname == 'student') {
    if ($student->lastaccess > 0) {
      $student->lastaccess = date('Y-m-d H:i:s', $student->lastaccess);
    } else {
      $student->lastaccess = get_string('never', 'local_listusers');
    }

    $templatecontext->students[] = (object) [
      'id' => $student->id,
      'name' => $student->username,
      'lastaccess' => $student->lastaccess,
      'group' => $groupname,
      'nrdziennika' => $userStudent->nr_dziennika
    ];
  }
}

$templatecontext->anyStudents = count($templatecontext->students) > 0 ? true : false;

//print_r($students);

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

echo '<h1>' . $templatecontext->headertext . '</h1><p>' . $templatecontext->texttodisplay . '</p>';

$uform = new filterUsers();
$uform->display();

echo $OUTPUT->render_from_template('local_listusers/manage', $templatecontext);

echo $OUTPUT->footer();