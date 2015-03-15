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
 * Library of interface functions and constants for module answersheet
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the answersheet specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_answersheet
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('NEWMODULE_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function answersheet_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the answersheet into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $answersheet Submitted data from the form in mod_form.php
 * @param mod_answersheet_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted answersheet record
 */
function answersheet_add_instance(stdClass $answersheet, mod_answersheet_mod_form $mform = null) {
    global $DB;

    $answersheet->timecreated = time();

    // You may have to add extra stuff in here.

    $answersheet->id = $DB->insert_record('answersheet', $answersheet);

    answersheet_grade_item_update($answersheet);

    return $answersheet->id;
}

/**
 * Updates an instance of the answersheet in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $answersheet An object from the form in mod_form.php
 * @param mod_answersheet_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function answersheet_update_instance(stdClass $answersheet, mod_answersheet_mod_form $mform = null) {
    global $DB;

    $answersheet->timemodified = time();
    $answersheet->id = $answersheet->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('answersheet', $answersheet);

    answersheet_grade_item_update($answersheet);

    return $result;
}

/**
 * Removes an instance of the answersheet from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function answersheet_delete_instance($id) {
    global $DB;

    if (! $answersheet = $DB->get_record('answersheet', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('answersheet', array('id' => $answersheet->id));
    $DB->delete_records('answersheet_attempt', array('answersheetid' => $answersheet->id));

    answersheet_grade_item_delete($answersheet);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $answersheet The answersheet instance record
 * @return stdClass|null
 */
function answersheet_user_outline($course, $user, $mod, $answersheet) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $answersheet the module instance record
 */
function answersheet_user_complete($course, $user, $mod, $answersheet) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in answersheet activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function answersheet_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link answersheet_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function answersheet_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link answersheet_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function answersheet_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function answersheet_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function answersheet_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of answersheet?
 *
 * This function returns if a scale is being used by one answersheet
 * if it has support for grading and scales.
 *
 * @param int $answersheetid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given answersheet instance
 */
function answersheet_scale_used($answersheetid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('answersheet', array('id' => $answersheetid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of answersheet.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any answersheet instance
 */
function answersheet_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('answersheet', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given answersheet instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $answersheet instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function answersheet_grade_item_update(stdClass $answersheet, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($answersheet->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($answersheet->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $answersheet->grade;
        $item['grademin']  = 0;
    } else if ($answersheet->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$answersheet->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/answersheet', $answersheet->course, 'mod', 'answersheet',
            $answersheet->id, 0, null, $item);
}

/**
 * Delete grade item for given answersheet instance
 *
 * @param stdClass $answersheet instance object
 * @return grade_item
 */
function answersheet_grade_item_delete($answersheet) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/answersheet', $answersheet->course, 'mod', 'answersheet',
            $answersheet->id, 0, null, array('deleted' => 1));
}

/**
 * Update answersheet grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $answersheet instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function answersheet_update_grades(stdClass $answersheet, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = mod_answersheet_attempt::get_last_completed_attempt_grade($answersheet, $userid);

    grade_update('mod/answersheet', $answersheet->course, 'mod', 'answersheet', $answersheet->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function answersheet_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for answersheet file areas
 *
 * @package mod_answersheet
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function answersheet_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the answersheet file areas
 *
 * @package mod_answersheet
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the answersheet's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function answersheet_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding answersheet nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the answersheet module instance
 * @param stdClass $course current course record
 * @param stdClass $module current answersheet instance record
 * @param cm_info $cm course module information
 */
//function answersheet_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
//}

/**
 * Extends the settings navigation with the answersheet settings
 *
 * This function is called when the context for the page is a answersheet module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $answersheetnode answersheet administration node
 */
function answersheet_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $answersheetnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Obtains the automatic completion state for this feedback based on the condition
 * in feedback settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function answersheet_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    $answersheet = $DB->get_record('answersheet', array('id' => $cm->instance), '*', MUST_EXIST);
    if (!$answersheet->completionsubmit && !$answersheet->completionpass) {
        return $type;
    }

    // If completion option is enabled, evaluate it and return true/false
    if ($answersheet->completionsubmit) {
        $params = array('userid' => $userid, 'answersheetid' => $answersheet->id);
        if (!$DB->record_exists_select('answersheet_attempt',
                'userid = :userid AND answersheetid = :answersheetid AND timecompleted IS NOT NULL',
                $params)) {
            return false;
        }
    }

    // Check for passing grade.
    if ($answersheet->completionpass) {
        require_once($CFG->libdir . '/gradelib.php');
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
                'itemmodule' => 'answersheet', 'iteminstance' => $cm->instance, 'outcomeid' => null));
        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, array($userid), false);
            if (!empty($grades[$userid])) {
                return $grades[$userid]->is_passed($item);
            }
        }
    }

    return false;
}