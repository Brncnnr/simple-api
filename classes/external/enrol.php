<?php
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
 */
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/lib/enrollib.php';

function local_pimcore_enrol_user($input)
{
    global $DB;

    $userid = $input->userid;
    $courseid = $input->courseid;
    $coursestart = $input->coursestart;
    $courseend = $input->courseend;
    $suspenion = $courseend;

    $instance = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol' => 'manual',
    ]);

    $enrolplugin = enrol_get_plugin($instance->enrol);
    $enrolplugin->enrol_user(
        $instance,
        $userid,
        $user_role = 5,
        $coursestart,
        $courseend
    );

    /** * fill suspend date */
    $user = \core_user::get_user($input->userid);
    profile_load_data($user);
    $pimcore_suspend = $user->profile_field_pimcore_suspend;
    if ($pimcore_suspend < $courseend) {
        $user->profile_field_pimcore_suspend = $courseend;
        profile_save_data($user);
    } else {
        $suspenion = $pimcore_suspend;
    }

    /** * response */
    print_r([
        'user enrolled' => [
            'user id' => $userid,
            'course id' => $courseid,
            'start date' => date('D M j G:i:s T Y', $coursestart),
            'end date' => date('D M j G:i:s T Y', $courseend),
        ],
        'date of suspension' => date('D M j G:i:s T Y', $suspenion),
    ]);
}

function local_pimcore_get_course($input)
{
    global $DB;

    $courseid = $input->courseid;

    $sql = 'select id from {course} where id= :id';

    if ($DB->record_exists_sql($sql, ['id' => $courseid])) {
        return true;
    } else {
        throw new Exception(
            'Course with id '
            . $input->courseid
            . ' does not exist, enrollment not possible'
        );
    }
}
