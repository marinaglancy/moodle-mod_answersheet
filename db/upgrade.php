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
 * This file keeps track of upgrades to the answersheet module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute answersheet upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_answersheet_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2015031504) {

        // Define field islast to be added to answersheet_attempt.
        $table = new xmldb_table('answersheet_attempt');
        $field = new xmldb_field('islast', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'grade');

        // Conditionally launch add field islast.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

        }

        $records = $DB->get_records_select('answersheet_attempt',
                'timecompleted IS NOT NULL', array(),
                'userid, timestarted DESC, id DESC',
                'id, answersheetid, userid');
        $processed = array();
        foreach ($records as $record) {
            if (!in_array($record->answersheetid.'-'.$record->userid, $processed)) {
                $DB->update_record('answersheet_attempt', array('id' => $record->id, 'islast' => 1));
                $processed[] = $record->answersheetid.'-'.$record->userid;
            }
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031504, 'answersheet');
    }

    if ($oldversion < 2017080600) {

        // Define field explanations to be added to answersheet.
        $table = new xmldb_table('answersheet');
        $field = new xmldb_field('explanations', XMLDB_TYPE_TEXT, null, null, null, null, null, 'answerslist');

        // Conditionally launch add field explanations.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2017080600, 'answersheet');
    }

    if ($oldversion < 2017080601) {

        // Define field explanationsformat to be added to answersheet.
        $table = new xmldb_table('answersheet');
        $field = new xmldb_field('explanationsformat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'explanations');

        // Conditionally launch add field explanationsformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2017080601, 'answersheet');
    }

    return true;
}
