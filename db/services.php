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
 *
 * @package   local_pimcore
 * @copyright 2022, Alex Süß <alexander.suess@kamedia.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$services = [
	'pimcore' => [
		'functions' => ['local_pimcore_sync'],
		'requiredcapability	' => '',
		'restrictedusers' => 0,
		'shortname' => 'pimcore',
		'enabled' => 1,
		'downloadfiles' => 0,
		'uploadfiles' => 0,
	],
];

$functions = [
	'local_pimcore_sync' => [
		'classname' => 'local_pimcore_sync',
		'methodname' => 'sync',
		'classpath' => 'local/pimcore/classes/external/sync.php',
		'description' => 'pimcore sync API',
		'type' => 'write',
		'ajax' => '',
		'capabilities' => 'moodle/role:assign',
	],
];
