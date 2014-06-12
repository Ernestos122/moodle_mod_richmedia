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
 * Define all the backup steps that will be used by the backup_richmedia_activity_task
 */

/**
 * Define the complete richmedia structure for backup, with file and id annotations
 */
class backup_richmedia_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $rich = new backup_nested_element('richmedia', array('id'), array(
            'name',
            'referenceslides',
            'referencesvideo',
            'referencesfond',
            'referencesxml',
            'sha1hash',
            'intro',
            'introformat',
            'width',
            'height',
            'theme',
            'html5',
            'presentor',
            'font',
            'fontcolor',
            'defaultview',
            'autoplay',
            'referencessynchro',
            'keywords',
            'quizid'
            )
        );

        $richmediatracks = new backup_nested_element('richmedia_tracks');

        $richmediatrack = new backup_nested_element('richmedia_track', array('id'), array(
            'userid', 'attempt', 'start', 'last'));

        $rich->add_child($richmediatracks);
        $richmediatracks->add_child($richmediatrack);

        // Define sources
        $rich->set_source_table('richmedia', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $richmediatrack->set_source_table('richmedia_track', array('richmediaid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $richmediatrack->annotate_ids('user', 'userid');

        // Define file annotations
        $rich->annotate_files('mod_richmedia', 'content', null); // This file area hasn't itemid
        $rich->annotate_files('mod_richmedia', 'zip', null); // This file area hasn't itemid
        $rich->annotate_files('mod_richmedia', 'picture', null); // This file area hasn't itemid
        $rich->annotate_files('mod_richmedia', 'package', null); // This file area hasn't itemid

        // Return the root element (richmedia), wrapped into standard activity structure
        return $this->prepare_activity_structure($rich);
    }
}
