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

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/group/lib.php');

class edit extends moodleform
{
  //Add elements to form
  public function definition()
  {
    global $CFG, $USER;

    $mform = $this->_form; // Don't forget the underscore! 

    $courses = get_courses();
    // print_r($courses);
    $choices = [];
    $choices[0] = get_string('choosecourse', 'local_addcoordinator');

    foreach ($courses as $key => $course) {
      if ($course->visible == 1 and $course->category > 0 and $course->category != 4) {
        $choices[$key] = $course->shortname;
      }
    }

    $mform->addElement('select', 'course', get_string('choosecourse', 'local_addcoordinator'), $choices);
    $mform->addRule('course', get_string('missingcourse'), 'required');

    $mform->addElement('text', 'schoolname',  get_string('schoolname', 'local_addcoordinator')); // Add elements to your form
    $mform->setType('schoolname', PARAM_NOTAGS);                   //Set type of element
    $mform->addRule('schoolname', get_string('missingschoolname', 'local_addcoordinator'), 'required');
    $mform->setDefault('schoolname', '');        //Default value

    $mform->addElement('text', 'classname',  get_string('classname', 'local_addcoordinator')); // Add elements to your form
    $mform->setType('classname', PARAM_NOTAGS);                   //Set type of element
    $mform->addRule('classname', get_string('missingclassname', 'local_addcoordinator'), 'required');
    $mform->setDefault('classname', '');        //Default value

    $mform->addElement('text', 'email',  get_string('email')); // Add elements to your form
    $mform->setType('email', PARAM_NOTAGS);                   //Set type of element
    $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
    $mform->setDefault('email', '');        //Default value

    $mform->addElement('text', 'firstname',  get_string('firstname')); // Add elements to your form
    $mform->setType('firstname', PARAM_NOTAGS);                   //Set type of element
    $mform->addRule('firstname', get_string('missingfirstname'), 'required');
    $mform->setDefault('firstname', '');        //Default value

    $mform->addElement('text', 'lastname',  get_string('lastname')); // Add elements to your form
    $mform->setType('lastname', PARAM_NOTAGS);                   //Set type of element
    $mform->addRule('lastname', get_string('missinglastname'), 'required');
    $mform->setDefault('lastname', '');        //Default value

    $mform->addElement('password', 'password', get_string('password')); // Add elements to your form
    $mform->addRule('password', get_string('missingpassword'), 'required');
    $mform->setDefault('password', '');        //Default value

    /*$choices = [];
    $choices[0] = get_string('studentsgroup', 'local_addcoordinator');
    $courses = enrol_get_all_users_courses($USER->id, true, ['id', 'fullname']);
    $groups = groups_get_my_groups();
    $groupings = [];
    $parentGroups = [];
    //print_r($courses);
    //print_r($groups);

    // Find schools from 'dziennik'
    foreach ($groups as $group) {
      if ($group->courseid == '10') {
        $context = context_course::instance($group->courseid);
        $roles = get_user_roles($context, $USER->id, true);
        $role = key($roles);
        $rolename = $roles[$role]->shortname;
        if ($rolename == 'teacherkeg') {
          $groupings[] = $group;
        }
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

          if ($rolename == 'teacherkeg') {
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

      if (count($choices) > 1) {
        $mform->addElement('select', 'group', get_string('studentsgroup', 'local_addcoordinator'), $choices);
        $mform->addRule('group', get_string('missingclass'), 'required');
      } else {
        $choices[0] = 'Wybierz szkołę';
        foreach ($parentGroups as $parentGroup) {
          $choices[$group->groupingid] = $parentGroup->coursename . ', ' . $parentGroup->groupingname;
        }

        $mform->addElement('select', 'grouping', get_string('schools'), $choices);
        $mform->addRule('grouping', get_string('missinggrouping'), 'required');
        $mform->addElement('text', 'groupname',  get_string('groupname', 'local_addcoordinator')); // Add elements to your form
        $mform->setType('groupname', PARAM_ALPHANUM);                   //Set type of element
        $mform->setDefault('groupname', '');        //Default value
        $mform->addRule('groupname', get_string('missinggroupname'), 'required');
      }

      $mform->addElement('text', 'email',  get_string('email')); // Add elements to your form
      $mform->setType('email', PARAM_EMAIL);                   //Set type of element
      $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
      $mform->setDefault('email', '');        //Default value

      $mform->addElement('text', 'firstname',  get_string('firstname')); // Add elements to your form
      $mform->setType('firstname', PARAM_ALPHA);                   //Set type of element
      $mform->addRule('firstname', get_string('missingfirstname'), 'required');
      $mform->setDefault('firstname', '');        //Default value

      $mform->addElement('text', 'lastname',  get_string('lastname')); // Add elements to your form
      $mform->setType('lastname', PARAM_ALPHA);                   //Set type of element
      $mform->addRule('lastname', get_string('missinglastname'), 'required');
      $mform->setDefault('lastname', '');        //Default value

      $mform->addElement('password', 'password', get_string('password')); // Add elements to your form
      $mform->addRule('password', get_string('missingpassword'), 'required');
      $mform->setDefault('password', '');        //Default value
    }*/

    $this->add_action_buttons();
  }
  //Custom validation should be added here
  function validation($data, $files)
  {
    return array();
  }
}