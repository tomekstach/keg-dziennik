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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/addusers/classes/form/edit.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/local/addusers/vendor/llagerlof/moodlerest/MoodleRest.php');

global $USER;

require_login();

$PAGE->set_url(new moodle_url('/local/addusers/manage.php'));
$PAGE->set_title(get_string('localuserheader', 'local_addusers'));

$templatecontext = (object) [
  'texttodisplay' => get_string('localusertext', 'local_addusers'),
  'headertext' => get_string('localuserheader', 'local_addusers')
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

$uform = new edit();

$templatecontext->anyStudents = false;
$templatecontext->students    = [];

//Form processing and displaying is done here
if ($uform->is_cancelled()) {
  //Handle form cancel operation, if cancel button is present on form
  \core\notification::add(get_string('formwascleared', 'local_addusers'), \core\output\notification::NOTIFY_WARNING);
  echo $OUTPUT->render_from_template('local_addusers/manage', $templatecontext);
  $uform->display();
} else if ($fromform = $uform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
  //print_r($fromform);
  if ((int) $fromform->studentsnumber == 0) {
    \core\notification::add(get_string('errornumberofstudents', 'local_addusers'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->render_from_template('local_addusers/manage', $templatecontext);
    $uform->display();
  } elseif ((int) $fromform->group == 0) {
    \core\notification::add(get_string('selectgroup', 'local_addusers'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->render_from_template('local_addusers/manage', $templatecontext);
    $uform->display();
  } else {
    $groups = groups_get_my_groups();
    $courseID = 0;

    foreach ($groups as $group) {
      if ($group->id == $fromform->group) {
        $courseID = $group->courseid;
      }
    }

    $token = get_config('local_addusers', 'apitoken');
    $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';

    // Generate users data
    $users = [];

    // Get first usernumber
    $groupUsers = groups_get_members($fromform->group, $fields = 'u.*', $sort = 'username ASC');

    $userNumber = 1;

    foreach ($groupUsers as $member) {
      if (strpos($member->username, 'g' . $fromform->group . 'u') !== false) {
        $userNameOld = explode('u', $member->username);
        if ($userNumber <= (int) $userNameOld[1]) {
          $userNumber = $userNameOld[1] + 1;
        }
      }
    }

    for ($i = 0; $i < (int) $fromform->studentsnumber; $i++) {
      $username = 'g' . $groups[$fromform->group]->groupid . 'u' . ($userNumber + $i);
      $users[] = [
        'username' => $username,
        'password' =>  generatePassword(),
        'firstname' =>  'Student',
        'lastname' => $username,
        'email' => $username . '@katalystengineering.org',
        'lang' => 'pl',
      ];
    }

    $MoodleRest = new MoodleRest($baseurl, $tokenobject->token);
    //$MoodleRest->setDebug();
    $newusers = $MoodleRest->request('core_user_create_users', array('users' => $users));

    $enrolments = [];
    $members    = [];

    foreach ($newusers as $newuser) {
      if ((int) $newuser['id'] > 0) {
        $enrolments[] = [
          'roleid' => '5',
          'userid' => (int) $newuser['id'],
          'courseid' => $courseID
        ];

        $members[] = [
          'userid' => (int) $newuser['id'],
          'groupid' => $fromform->group
        ];
      }
    }

    if (count($enrolments) > 0) {
      $response = $MoodleRest->request('enrol_manual_enrol_users', array('enrolments' => $enrolments));
      $response = $MoodleRest->request('core_group_add_group_members', array('members' => $members));

      $hash     = generateHash();
      $fileName = __DIR__ . '/tmp/' . $hash . '.csv';
      $fileUrl  = new moodle_url('/local/addusers/tmp/' . $hash . '.csv');
      $fp       = fopen($fileName, 'w');
      fputcsv($fp, ['L.p.', 'Nazwa uzytkownika', 'Haslo']);

      $i = 1;
      foreach ($users as &$user) {
        foreach ($newusers as $newuser) {
          if ($user['username'] == $newuser['username']) {
            $templatecontext->students[] = (object) [
              'lp' => $i,
              'name' => $user['username'],
              'password' => $user['password']
            ];
            fputcsv($fp, [$i, $user['username'], $user['password']]);
            $i++;
          }
        }
      }

      fclose($fp);

      if ($i > 1) {
        $templatecontext->anyStudents = true;
        $templatecontext->fileurl = $fileUrl;
      } else {
        $templatecontext->anyStudents = false;
      }
    }

    \core\notification::add(get_string('userswereadded', 'local_addusers'), \core\output\notification::NOTIFY_SUCCESS);
  }

  echo $OUTPUT->render_from_template('local_addusers/manage', $templatecontext);
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.

  // $tokenurl = $CFG->wwwroot . '/login/token.php?username=&password=&service=kegmanager';
  // $tokenresponse = file_get_contents($tokenurl);
  // $tokenobject = json_decode($tokenresponse);

  // print_r($tokenobject);

  echo $OUTPUT->render_from_template('local_addusers/manage', $templatecontext);

  //Set default data (if any)
  //$uform->set_data($toform);
  //displays the form
  $uform->display();
}

function generatePassword(): string
{
  $chars = [
    (object) ['string' => 'abcdefghijklmnopqrstuvwxyz', 'lenght' => strlen('abcdefghijklmnopqrstuvwxyz') - 1],
    (object) ['string' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'lenght' => strlen('ABCDEFGHIJKLMNOPQRSTUVWXYZ') - 1],
    (object) ['string' => '1234567890', 'lenght' => strlen('1234567890') - 1],
    (object) ['string' => '!@#$%^&*,.?;+_-=:', 'lenght' => strlen('!@#$%^&*,.?;+_-=:') - 1]
  ];
  $pass = [];

  for ($i = 0; $i < 3; $i++) {
    shuffle($chars);
    foreach ($chars as $value) {
      $n = rand(0, $value->lenght);
      $pass[] = $value->string[$n];
    }
  }

  return implode($pass);
}

function generateHash(): string
{
  return urlencode(random_string(30));
}

echo $OUTPUT->footer();