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
 * Plugin class for external content module.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_externalcontent;

/**
 * Plugin class for external content module.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class plugin {

    /**
     * Component name.
     */
    const COMPONENT = 'mod_externalcontent';

    /**
     * Helper generates a random password.
     *
     * @param int $length
     *
     * @return string
     */
    protected static function random_password($length = 24) {
        $password = substr(
            str_shuffle(
                str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / 62))
            ), 1, $length
        );
        return $password;
    }

    /**
     * This function checks if a xapi credentials have been set for this site.
     *
     * If they do not exist it generates a new set.
     *
     * @return void;
     */
    public static function set_randomlrscredentials() {

        $config = get_config('externalcontent');
        // If we already have a default username no need to set.
        if (empty($config->xapidefaultusername)) {
            set_config('xapidefaultusername', static::random_password(24), 'externalcontent');
        }

        // If we already have a default password no need to set.
        if (empty($config->xapidefaultpassword)) {
            set_config('xapidefaultpassword', static::random_password(24), 'externalcontent');
        }

        $config = get_config('externalcontent');
        // If we dont already have a username set to default.
        if (empty($config->xapiusername)) {
            set_config('xapiusername', $config->xapidefaultusername, 'externalcontent');
        }
        // If we dont already have a password set to default.
        if (empty($config->xapipassword)) {
            set_config('xapipassword', $config->xapidefaultpassword, 'externalcontent');
        }
    }
}
