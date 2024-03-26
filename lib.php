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
 * @package   local_pimcore
 * @copyright 2022, Alex Süß <alexander.suess@kamedia.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @prettier
 */
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/user/lib.php';

function local_pimcore_input_object(
    $pimcoreuuid,
    $username,
    $lastname,
    $firstname,
    $courseid,
    $coursestart,
    $courseend
) {
    global $DB;

    $input = new StdClass();

    $input->pimcoreuuid = $pimcoreuuid;
    $input->username = $username;
    $input->lastname = $lastname;
    $input->firstname = $firstname;
    $input->courseid = $courseid;
    $input->coursestart = $coursestart;
    $input->courseend = $courseend;

    return $input;
}

/**
 * create json output
 */
function local_pimcore_json_print($data)
{
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo $json;
    return $json;
}

function local_pimcore_get_userid($input)
{
    global $DB;

    $sqlid = 'select userid from {user_info_data} where data= :data';
    $sqlemail = 'select email from {user} where email= :email';

    if (!$DB->record_exists_sql($sqlemail, ['email' => $input->username])) {
        if ($DB->record_exists_sql($sqlid, ['data' => $input->pimcoreuuid])) {
            // $user = $DB->get_record('user', ['email' => $username], 'id');
            $userid = $DB->get_record_sql($sqlid, ['data' => $input->pimcoreuuid]);
            $input->userid = $userid->userid;
            $user = \core_user::get_user($userid->userid);
            if ($user->suspended == 1) {
                $user->suspended = 0;
                user_update_user($user, false, false);
                profile_load_data($user);
                $user->profile_field_pimcore_delete = 0;
                profile_save_data($user);
            }
            return $userid->userid;
        }
    } else {
        throw new Exception('user with ' . $input->username . ' exists already');
    }
}

function local_pimcore_create_user($input)
{
    $user = new stdClass();

    $user->username = $input->username;
    $user->firstname = $input->firstname;
    $user->lastname = $input->lastname;
    $user->email = $input->username;
    $user->confirmed = 1;
    $user->mnethostid = 1;
    $user->auth = 'manual';

    $user->id = user_create_user($user);
    profile_load_data($user);
    // pimcore UUID
    $user->profile_field_pimcore_uuid = $input->pimcoreuuid;
    profile_save_data($user);
    $input->userid = $user->id;
    print_r($user);
}

function local_pimcore_update_user($input)
{
    global $DB, $USER;

    /** todo should we skip update user complety if there's no change?? */
    $db_user = \core_user::get_user($input->userid);
    $updateresp = new stdClass();
    if (
        $db_user->username != $input->username ||
        $db_user->email != $input->username
    ) {
        $db_user->username = $input->username;
        $db_user->email = $input->username;
        /** * user update info */
        $updateresp->username = $input->username;
        $updateresp->email = $input->username;
    }
    if ($db_user->lastname != $input->lastname) {
        $db_user->lastname = $input->lastname;
        /** * user update info */
        $updateresp->lastname = $input->lastname;
    }
    if ($db_user->firstname != $input->firstname) {
        $db_user->firstname = $input->firstname;
        /** * user update info */
        $updateresp->firstname = $input->firstname;
    }
    $DB->update_record('user', $db_user);
    print_r(['User updated' => $updateresp]);
}

function local_pimcore_log($input)
{
    global $DB;

    $table = 'local_pimcore_log';

    $log = new StdClass();

    $log->requesttime = date('D j.m.Y G:i:s', time());
    $log->requestmethod = $_SERVER['REQUEST_METHOD'];
    $log->pimcoreuuid = $input->pimcoreuuid;
    $log->username = $input->username;
    $log->userid = $input->userid;
    $log->lastname = $input->lastname;
    $log->firstname = $input->firstname;
    $log->courseid = $input->courseid;
    $log->coursestart = date('D j.m.Y G:i:s', $input->coursestart);
    $log->courseend = date('D j.m.Y G:i:s', $input->courseend);

    $DB->insert_record($table, $log);
}

function local_pimcore_debug_file($input)
{
    global $DB, $CFG;

    $input->coursestart = date('D j.m.Y G:i:s', $input->coursestart);
    $input->courseend = date('D j.m.Y G:i:s', $input->courseend);
    $debug = [
        'requesttime' => date('D j.m.Y G:i:s', time()),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request' => $input,
    ];
    file_put_contents(
        $CFG->dataroot . '/debug.json',
        date('d.m.Y - H:i:s') . PHP_EOL . print_r($debug, true),
        FILE_APPEND
    );
}
