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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_richmedia_activity_task
 */

/**
 * Structure step to restore one richmedia activity
 */
class restore_richmedia_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('richmedia', '/activity/richmedia');
        if ($userinfo) {
            $paths[] = new restore_path_element('richmedia_track', '/activity/richmedia/richmedia_tracks/richmedia_track');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_richmedia($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // insert the richmedia record
        $newitemid = $DB->insert_record('richmedia', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
	}

    protected function process_richmedia_track($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->richmediaid = $this->get_new_parentid('richmedia');
        $data->userid = $this->get_mappingid('user', $data->userid);;

        $newitemid = $DB->insert_record('richmedia_track', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add scorm related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_richmedia', 'content', null);
        $this->add_related_files('mod_richmedia', 'zip', null);
        $this->add_related_files('mod_richmedia', 'picture', null);
        $this->add_related_files('mod_richmedia', 'package', null);
    }
}
