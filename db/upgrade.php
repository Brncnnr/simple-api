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
 *
 * @package   local_pimcore
 * @copyright 2022, Alex Süß <alexander.suess@kamedia.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
function xmldb_local_pimcore_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = TRUE;

    /*
    if ($oldversion < 2022100118) {

        // Define table local_pimcore_log to be created.
        $table = new xmldb_table('local_pimcore_log');

        // Adding fields to table local_pimcore_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('requesttime', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('requestmethod', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('pimcoreuuid', XMLDB_TYPE_CHAR, '11', null, null, null, null);
        $table->add_field('username', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('lastname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('firstname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('coursename', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('coursestart', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('courseend', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_pimcore_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_pimcore_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pimcore savepoint reached.
        upgrade_plugin_savepoint(true, 2022100119, 'local', 'pimcore');
    }
    */
    /*
        if ($oldversion < 2022100119) {

        // Changing type of field requesttime on table local_pimcore_log to text.
        $table = new xmldb_table('local_pimcore_log');
        $field = new xmldb_field('requesttime', XMLDB_TYPE_TEXT, null, null, null, null, null, 'id');

        // Launch change of type for field requesttime.
        $dbman->change_field_type($table, $field);

        // Pimcore savepoint reached.
        upgrade_plugin_savepoint(true, 2022100119, 'local', 'pimcore');
    }
    */
    if ($oldversion < 2022100120) {

        // Define field userid to be added to local_pimcore_log.
        $table = new xmldb_table('local_pimcore_log');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'username');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pimcore savepoint reached.
        upgrade_plugin_savepoint(true, 2022100120, 'local', 'pimcore');
    }

    return $result;
}