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
 * @copyright  2015 Your Name
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

    if ($oldversion < 2015031401) {

        // Define field questionscount to be added to answersheet.
        $table = new xmldb_table('answersheet');
        $field = new xmldb_field('questionscount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade');

        // Conditionally launch add field questionscount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('questionsoptions', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'A,B,C,D', 'questionscount');

        // Conditionally launch add field questionsoptions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('answerslist', XMLDB_TYPE_TEXT, null, null, null, null, null, 'questionsoptions');

        // Conditionally launch add field answerslist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031401, 'answersheet');
    }

    if ($oldversion < 2015031402) {

        // Define table answersheet_attempt to be created.
        $table = new xmldb_table('answersheet_attempt');

        // Adding fields to table answersheet_attempt.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('answers', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);

        // Adding keys to table answersheet_attempt.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for answersheet_attempt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031402, 'answersheet');
    }

    if ($oldversion < 2015031403) {

        // Rename field attemptid on table answersheet_attempt to answersheetid.
        $table = new xmldb_table('answersheet_attempt');
        $field = new xmldb_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field attemptid.
        $dbman->rename_field($table, $field, 'answersheetid');

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031403, 'answersheet');
    }

    if ($oldversion < 2015031404) {

        // Define field completionsubmit to be added to answersheet.
        $table = new xmldb_table('answersheet');
        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'answerslist');

        // Conditionally launch add field completionsubmit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completionpass', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completionsubmit');

        // Conditionally launch add field completionpass.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031404, 'answersheet');
    }

    if ($oldversion < 2015031405) {

        // Rename field attemptid on table answersheet_attempt to answersheetid.
        $table = new xmldb_table('answersheet_attempt');
        $field = new xmldb_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field attemptid.
        $dbman->rename_field($table, $field, 'answersheetid');

        // Answersheet savepoint reached.
        upgrade_mod_savepoint(true, 2015031405, 'answersheet');
    }

    return true;
}
