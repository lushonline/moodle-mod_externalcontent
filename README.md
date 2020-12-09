# External Content with Completion #
[![Build Status](https://travis-ci.org/lushonline/moodle-mod_externalcontent.svg?branch=moodle33)](https://travis-ci.org/lushonline/moodle-mod_externalcontent)

The module enables a teacher to create a resource using the text editor.

The resource can display text, images, sound, video and web links.

There are two versions depending on the Moodle version used:

|BRANCH         |MOODLE VERSIONS|
|---------------|---------------|
|[moodle33](https://github.com/lushonline/moodle-mod_externalcontent/tree/moodle33)|v3.2 - v3.4|
|[master](https://github.com/lushonline/moodle-mod_externalcontent/)|v3.5 - v3.10|

External content activities and Single Activity courses can be bulk loaded and updated using [moodle-tool_uploadexternalcontent](https://github.com/lushonline/moodle-tool_uploadexternalcontent)

Advantages of using the External content module rather than the standard page module is
that optionally if the content has links to an external site that records a completion status
the completion can then be imported using [moodle-tool_uploadexternalcontentresults](https://github.com/lushonline/moodle-tool_uploadexternalcontentresults)

- [Installation](#installation)

## Installation

---
1. Install the External content activity module:

   ```sh
   git clone https://github.com/lushonline/moodle-mod_externalcontent.git mod/externalcontent
   ```

   Or install via the Moodle plugin directory:

   https://moodle.org/plugins/mod_externalcontent

## License ##

2019-2020 LushOnline

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Acknowledgements
This was inspired in part by the great work of [Petr Skoda](http://skodak.org) on the core [mod\page](https://github.com/moodle/moodle/tree/master/mod/page)
