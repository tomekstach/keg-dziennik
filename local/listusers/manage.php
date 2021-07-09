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
require_once($CFG->dirroot . '/group/lib.php');

global $USER;

require_login();

$PAGE->set_url(new moodle_url('/local/addusers/manage.php'));
$PAGE->set_title(get_string('localuserheader', 'local_listusers'));

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

$choices = [];
    $choices[0] = get_string('studentsgroup', 'local_addusers');
    $courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups = groups_get_my_groups();
    $groupings = [];
    $parentGroups = [];
    //print_r($courses);
    //print_r($groups);

    // Find schools from 'dziennik'
    foreach ($groups as $group) {
      if ($group->courseid == '10') {
        $groupings[] = $group;
      }
    }

    // Find schools in courses where user has role teacherkeg and it is not 'dziennik'
    if (count($groupings) > 0) {
      foreach ($courses as $course) {
        if ($course->category != '4') {
          $context = context_course::instance($course->id);
          $roles = get_user_roles($context, $USER->id, true);
          $role = key($roles);
          $rolename = $roles[$role]->shortname;

          if ($rolename == 'teacher') {
            foreach ($groupings as $group) {
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
          $choices[$group->id . '-' . $parentGroup->schoolid] = $parentGroup->coursename . ', ' . $parentGroup->groupingname . ', ' . $group->name;
        }
      }
    }

$students = groups_get_members($groupid, $fields='u.*', $sort='lastname ASC');

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_listusers/manage', $templatecontext);

echo 'test';

echo $OUTPUT->footer();