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

require_once "$CFG->libdir/formslib.php";

class filterUsers extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $USER;

        $mform = $this->_form; // Don't forget the underscore!

        $choices = [];
        $choices[0] = get_string('studentsgroup', 'local_addteachers');
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
                            $parentGroup->schoolid = intval($group->id);

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
                        $choices[$parentGroup->courseid . '-' . $group->id] = $parentGroup->coursename . ', ' . $parentGroup->groupingname . ', ' . $group->name;
                    }
                }
            }
        }
        $mform->addElement('select', 'group', get_string('studentsgroup', 'local_teachers'), $choices);
    }
    //Custom validation should be added here
    public function validation($data, $files)
    {
        return array();
    }
}
