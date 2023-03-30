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
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/user/lib.php';

global $USER, $PAGE, $DB;

require_login();

if (!isguestuser()) {
    $userid = optional_param('id', '', PARAM_INT);
    $userObj = $DB->get_record('user', array('id' => $userid));
    $user = null;

    $userObj->firstname = (string) optional_param('firstname', 0, PARAM_NOTAGS);
    $userObj->lastname = (string) optional_param('lastname', 0, PARAM_NOTAGS);
    $userObj->email = trim((string) optional_param('email', 0, PARAM_EMAIL));
    if ($userObj->username !== $userObj->email) {
        $userObj->username = $userObj->email;
        $user = $DB->get_record('user', array('username' => $userObj->username, 'auth' => 'manual'));
    }

    // print_r($userObj);

    $errmsg = '';

    if (strlen($userObj->firstname) < 2) {
        $errmsg = '{"message": "Error: Zła wartość w polu imię!", "error": true}';
    }

    if (strlen($userObj->lastname) < 2) {
        $errmsg = '{"message": "Error: Zła wartość w polu nazwisko!", "error": true}';
    }

    if (!validate_email($userObj->email) or is_object($user)) {
        $errmsg = '{"message": "Error: Zła wartość w polu email!", "error": true}';
    }

    if ($errmsg === '') {
        // Update user with new profile data.
        user_update_user($userObj, false);

        echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
    } else {
        echo $errmsg;
    }
}
