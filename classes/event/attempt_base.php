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
 * Base class for the mod_answersheet attempt events.
 *
 * @package    mod_answersheet
 * @copyright  2017 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

namespace mod_answersheet\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Base class for the mod_answersheet attempt events.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int cmid: course module id.
 *      - int instanceid: id of instance.
 * }
 *
 * @package    mod_answersheet
 * @copyright  2017 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
abstract class attempt_base extends \core\event\base {

    /**
     * Set basic properties for the event.
     */
    protected function init() {
        global $CFG;

        $this->data['objecttable'] = 'answersheet_attempt';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Creates an instance from the record from db table answersheet_attempt
     *
     * @param stdClass $completed
     * @param stdClass|cm_info $cm
     * @return self
     */
    public static function create_from_record($attempt, $cm) {
        $event = self::create(array(
            'objectid' => $attempt->id,
            'context' => \context_module::instance($cm->id),
            'other' => array(
                'cmid' => $cm->id,
                'instanceid' => $attempt->answersheetid,
            )
        ));
        $event->add_record_snapshot('answersheet_attempt', $attempt);
        return $event;
    }

    /**
     * Returns relevant URL based on the anonymous mode of the response.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/answersheet/view.php', array('id' => $this->other['cmid'],
            'attempt' => $this->objectid));
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception in case of any problems.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['cmid'])) {
            throw new \coding_exception('The \'cmid\' value must be set in other.');
        }
        if (!isset($this->other['instanceid'])) {
            throw new \coding_exception('The \'instanceid\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'answersheet_attempt', 'restore' => 'answersheet_attempt');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['cmid'] = array('db' => 'course_modules', 'restore' => 'course_module');
        $othermapped['instanceid'] = array('db' => 'answersheet', 'restore' => 'answersheet');

        return $othermapped;
    }
}

