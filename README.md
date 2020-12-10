# External Content with Completion (EXPERIMENTAL BRANCH)
![Moodle Plugin CI](https://github.com/lushonline/moodle-mod_externalcontent/workflows/Moodle%20Plugin%20CI/badge.svg?branch=experimental)

The module enables a teacher to create a resource using the text editor.

The resource can display text, images, sound, video and web links.

External content activities and Single Activity courses can be bulk loaded and updated using [moodle-tool_uploadexternalcontent](https://github.com/lushonline/moodle-tool_uploadexternalcontent)

Advantages of using the External content module rather than the standard page module is
that optionally if the content has links to an external site that records a completion status
the completion can then be imported using [moodle-tool_uploadexternalcontentresults](https://github.com/lushonline/moodle-tool_uploadexternalcontentresults)

- [Installation](#installation)

## Installation

---

1. Install the External content activity module experimental branch:

   ```sh
   git clone --branch experimental https://github.com/lushonline/moodle-mod_externalcontent.git mod/externalcontent
   ```

## Configuring xAPI Realtime Tracking (Experimental)

The experimental xAPI Realtime Tracking implements basic LRS Statement API functionality.

Primarily:

* [Statements Resource](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#stmtres) - This is WRITE ONLY and so supports POST, PUT only. GET requests return a 401 Status
* [About Resource](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#aboutresource) - GET

The statements received are parsed, and used to set the viewed status, completion status and score for the content if available.

---

1. Configure the username/password which are used for basic authentication of the basic LRS endpoint. The defaults are random strings setup when module is installed.
2. Set the xAPI verbs that will be used to indicate the content has been "completed", if a verb that matches one of these values is received the completed externally flag is updated. Any other verb simply sets the viewed flag.

Configure your external content provider to connect to the "basic" LRS implemented here:

- LRS - https://{moodlehostname}/mod/externalcontent/lrs/index.php
- Authentication - Basic Authentication using the username/password you configure

For the tracking to work:

- Statement Object ID and the Moodle Course ID Numbers must match
- Statement Actor must use the account [Inverse Functional Identifiers](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#inversefunctional) and the name must match the Moodle Username.

## License

2019-2020 LushOnline

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <http://www.gnu.org/licenses/>.

## Acknowledgements

This was inspired in part by the great work of [Petr Skoda](http://skodak.org) on the core [mod\page](https://github.com/moodle/moodle/tree/master/mod/page)
