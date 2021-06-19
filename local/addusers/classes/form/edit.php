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
 * @package    local_addusers
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class edit extends moodleform
{
  //Add elements to form
  public function definition()
  {
    global $CFG, $USER;

    $mform = $this->_form; // Don't forget the underscore! 

    $mform->addElement('text', 'studentsnumber', 'Number of students'); // Add elements to your form
    $mform->setType('studentsnumber', PARAM_INT);                   //Set type of element
    $mform->setDefault('studentsnumber', 0);        //Default value

    $choices = [];
    $choices[0] = 'Wybierz grupę';
    $courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups = groups_get_my_groups();
    //print_r($courses);
    //print_r($groups);
    foreach ($courses as $course) {
      $context = context_course::instance($course->id);
      $roles = get_user_roles($context, $USER->id, true);
      $role = key($roles);
      $rolename = $roles[$role]->shortname;
      foreach ($groups as $group) {
        if ($group->courseid == $course->id and $rolename == 'teacher') {
          $choices[$group->id] = $group->name;
        }
      }
    }
    $mform->addElement('select', 'group', 'Students group', $choices);

    $this->add_action_buttons();
  }
  //Custom validation should be added here
  function validation($data, $files)
  {
    return array();
  }
}