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

require_once $CFG->libdir . '/formslib.php';
require_once $CFG->dirroot . '/group/lib.php';

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

        $mform->addElement('text', 'schoolname', get_string('schoolname', 'local_addcoordinator')); // Add elements to your form
        $mform->setType('schoolname', PARAM_NOTAGS); //Set type of element
        $mform->addRule('schoolname', get_string('missingschoolname', 'local_addcoordinator'), 'required');
        $mform->setDefault('schoolname', ''); //Default value

        $mform->addElement('text', 'classname', get_string('classname', 'local_addcoordinator')); // Add elements to your form
        $mform->setType('classname', PARAM_NOTAGS); //Set type of element
        $mform->addRule('classname', get_string('missingclassname', 'local_addcoordinator'), 'required');
        $mform->setDefault('classname', ''); //Default value

        $mform->addElement('text', 'email', get_string('email')); // Add elements to your form
        $mform->setType('email', PARAM_NOTAGS); //Set type of element
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
        $mform->setDefault('email', ''); //Default value

        $mform->addElement('text', 'firstname', get_string('firstname')); // Add elements to your form
        $mform->setType('firstname', PARAM_NOTAGS); //Set type of element
        $mform->addRule('firstname', get_string('missingfirstname'), 'required');
        $mform->setDefault('firstname', ''); //Default value

        $mform->addElement('text', 'lastname', get_string('lastname')); // Add elements to your form
        $mform->setType('lastname', PARAM_NOTAGS); //Set type of element
        $mform->addRule('lastname', get_string('missinglastname'), 'required');
        $mform->setDefault('lastname', ''); //Default value

        $mform->addElement('password', 'password', get_string('password')); // Add elements to your form
        $mform->setType('password', PARAM_NOTAGS); //Set type of element
        $mform->addRule('password', get_string('missingpassword'), 'required');
        $mform->setDefault('password', ''); //Default value

        $this->add_action_buttons();
    }

    //Custom validation should be added here
    public function validation($data, $files)
    {
        return array();
    }

    // Reset form values
    public function reset()
    {
        $this->_form->updateSubmission(null, null);
    }
}
