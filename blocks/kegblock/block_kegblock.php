<?php
// This file is part of Moodle - http://moodle.org/
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
 * Form for editing HTML block instances.
 *
 * @package   block_kegblock
 * @copyright 2021 AstoSoft (https://astosoft.pl)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_kegblock extends block_base
{

  function init()
  {
    $this->title = get_string('managestudents', 'block_kegblock');
  }

  function instance_allow_multiple()
  {
    return true;
  }

  function get_content()
  {
    global $CFG, $USER;

    if ($this->content !== NULL) {
      return $this->content;
    }

    $this->content = new stdClass;
    $this->content->text = '';
    $this->content->footer = '';

    $courses  = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups   = groups_get_my_groups();

    foreach ($courses as $course) {
      $context = context_course::instance($course->id);
      $roles = get_user_roles($context, $USER->id, true);
      $role = key($roles);
      $rolename = $roles[$role]->shortname;
      foreach ($groups as $group) {
        if ($group->courseid == $course->id and $rolename == 'teacher') {
          $this->content->text = '<a href="' . $CFG->wwwroot . '/local/addusers/manage.php">' . get_string('addstudents', 'block_kegblock') . '</a><br/>';
          $this->content->text .= '<a href="' . $CFG->wwwroot . '/local/listusers/manage.php">' . get_string('liststudents', 'block_kegblock') . '</a>';
          $this->title = get_string('managestudents', 'block_kegblock');
        } elseif ($group->courseid == $course->id and $rolename == 'teacherkeg') {
          $this->content->text = '<a href="' . $CFG->wwwroot . '/local/addteachers/manage.php">' . get_string('addteachers', 'block_kegblock') . '</a><br/>';
          $this->content->text .= '<a href="' . $CFG->wwwroot . '/local/teachersgroups/manage.php">' . get_string('teachersgroups', 'block_kegblock') . '</a>';
          $this->title = get_string('manageteachers', 'block_kegblock');
        }
      }
    }

    return $this->content;
  }
}