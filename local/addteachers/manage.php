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
require_once($CFG->dirroot . '/local/addteachers/classes/form/edit.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/local/addteachers/vendor/llagerlof/moodlerest/MoodleRest.php');

global $USER;

require_login();

$PAGE->set_url(new moodle_url('/local/addteachers/manage.php'));
$PAGE->set_title(get_string('localteacherheader', 'local_addteachers'));

$templatecontext = (object) [
  'texttodisplay' => get_string('localteachertext', 'local_addteachers'),
  'headertext' => get_string('localteacherheader', 'local_addteachers')
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

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_addteachers/manage', $templatecontext);

$uform = new edit();

//Form processing and displaying is done here
if ($uform->is_cancelled()) {
  //Handle form cancel operation, if cancel button is present on form
  \core\notification::add(get_string('formwascleared', 'local_addteachers'), \core\output\notification::NOTIFY_WARNING);
  $uform->display();
} else if ($fromform = $uform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
  //print_r($fromform);
  if ((int) $fromform->group == 0) {
    \core\notification::add(get_string('selectgroup', 'local_addteachers'), \core\output\notification::NOTIFY_ERROR);
    $uform->display();
  } else {
    $groups = groups_get_my_groups();
    //print_r($groups);
    $courseID   = 0;
    $groupID    = explode('-', $fromform->group)[0];
    $groupingID = explode('-', $fromform->group)[1];

    foreach ($groups as $group) {
      if ($group->id == $groupID) {
        $courseID = $group->courseid;
      }
    }

    // Find schools from 'dziennik'
    foreach ($groups as $group) {
      if ($group->courseid == '10') {
        $context = context_course::instance($group->courseid);
        $roles = get_user_roles($context, $USER->id, true);
        $role = key($roles);
        $rolename = $roles[$role]->shortname;
        if ($rolename == 'teacherkeg') {
          $groupings[] = $group->name;
        }
      }
    }

    $tokenurl = $CFG->wwwroot . '/login/token.php?username=wiktor&password=!53W7qbec&service=kegmanager';

    $tokenresponse = file_get_contents($tokenurl);

    $tokenobject = json_decode($tokenresponse);

    if (!empty($tokenobject->error)) {
      \core\notification::add($tokenobject->error, \core\output\notification::NOTIFY_ERROR);
    } else {
      $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';

      // Create user's data
      $users = [];
      $users[] = [
        'username' => $fromform->email,
        'password' =>  $fromform->password,
        'firstname' =>  $fromform->firstname,
        'lastname' => $fromform->lastname,
        'email' => $fromform->email,
        'lang' => 'pl',
      ];

      $MoodleRest = new MoodleRest($baseurl, $tokenobject->token);
      //$MoodleRest->setDebug();
      try {
        $newusers = $MoodleRest->request('core_user_create_users', array('users' => $users));

        $enrolmentsG  = [];
        $enrolmentsD  = [];
        $membersG     = [];
        $membersD     = [];

        foreach ($newusers as $newuser) {
          if ((int) $newuser['id'] > 0) {
            $enrolmentsG[] = [
              'roleid' => '4',
              'userid' => (int) $newuser['id'],
              'courseid' => $courseID
            ];

            $enrolmentsD[] = [
              'roleid' => '5',
              'userid' => (int) $newuser['id'],
              'courseid' => '10'
            ];

            $membersG[] = [
              'userid' => (int) $newuser['id'],
              'groupid' => $groupID
            ];

            $membersD[] = [
              'userid' => (int) $newuser['id'],
              'groupid' => $groupingID
            ];
          }
        }

        if (count($enrolmentsG) > 0) {
          $response = $MoodleRest->request('enrol_manual_enrol_users', array('enrolments' => $enrolmentsG));
          $response = $MoodleRest->request('enrol_manual_enrol_users', array('enrolments' => $enrolmentsD));
          $response = $MoodleRest->request('core_group_add_group_members', array('members' => $membersG));
          $response = $MoodleRest->request('core_group_add_group_members', array('members' => $membersD));

          $i = 1;
          echo 'Dane nauczyciela:<br/>';
          foreach ($users as &$user) {
            foreach ($newusers as $newuser) {
              if ($user['username'] == $newuser['username']) {
                $user->id = $newuser['id'];
                echo $i . '. ' . $user['email'] . ': ' . $user['firstname'] . ' ' . $user['lastname'] . '<br/>';
                $i++;
              }
            }
          }
          \core\notification::add(get_string('teacherwasadded', 'local_addteachers'), \core\output\notification::NOTIFY_SUCCESS);
        } else {
          \core\notification::add(get_string('errorteachernotadded', 'local_addteachers'), \core\output\notification::NOTIFY_ERROR);
          $uform->display();
        }
      } catch (Exception $th) {
        \core\notification::add($th->getMessage(), \core\output\notification::NOTIFY_ERROR);
        $uform->display();
      }
    }
  }
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.

  //Set default data (if any)
  //$uform->set_data($toform);
  //displays the form
  $uform->display();
}

echo $OUTPUT->footer();