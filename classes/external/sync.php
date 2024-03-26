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
require_once __DIR__ . '/../../../../config.php';
require_once $CFG->libdir . '/externallib.php';
require_once $CFG->dirroot . '/user/lib.php';
require_once $CFG->dirroot . '/user/profile/lib.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once __DIR__ . '/../../lib.php';
require_once __DIR__ . '/enrol.php';

class local_pimcore_sync extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    const PARAM_DEFAULT = 'PARAM_DEFAULT';

    public static function sync_parameters()
    {
        return new external_function_parameters([
            'pimcoreuuid' => new external_value(PARAM_INT, 'pimcoreuuid'),
            'username' => new external_value(PARAM_EMAIL, 'username'),
            'lastname' => new external_value(PARAM_NOTAGS, 'lastname'),
            'firstname' => new external_value(PARAM_NOTAGS, 'firstname'),
            'courseid' => new external_value(PARAM_INT, 'courseid'),
            'coursestart' => new external_value(PARAM_INT, 'coursestart'),
            'courseend' => new external_value(PARAM_INT, 'courseend'),
            'debug' => new external_value(PARAM_BOOL, self::PARAM_DEFAULT, false),
        ]);
    }

    /**
     * Returns users userid
     * @return int userid
     */
    public static function sync(
        $pimcoreuuid,
        $username,
        $lastname,
        $firstname,
        $courseid,
        $coursestart,
        $courseend,
        $debug
    ) {
        global $USER, $CFG;

        /** moodle required validaton of params */
        $params = self::validate_parameters(self::sync_parameters(), [
            'pimcoreuuid' => $pimcoreuuid,
            'username' => $username,
            'lastname' => $lastname,
            'firstname' => $firstname,
            'courseid' => $courseid,
            'coursestart' => $coursestart,
            'courseend' => $courseend,
            'debug' => $debug,
        ]);

        /**
         * create input object
         * validate data before
         */
        $input = local_pimcore_input_object(
            $pimcoreuuid,
            $username,
            $lastname,
            $firstname,
            $courseid,
            $coursestart,
            $courseend
        );

        if (local_pimcore_get_userid($input)) {
            /** update user */
            local_pimcore_update_user($input);
        } else {
            /** create User */
            local_pimcore_create_user($input);
        }
        if (local_pimcore_get_course($input)) {
            local_pimcore_enrol_user($input);
        }
        /** * create Debug */
        local_pimcore_log($input);
        if ($debug) {
            local_pimcore_debug_file($input);
        }
        return $USER->id;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function sync_returns()
    {
        return new external_value(PARAM_TEXT, 'Users ID');
    }
}
