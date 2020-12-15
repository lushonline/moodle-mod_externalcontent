# External Content with Completion
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

## Configuring xAPI Realtime Tracking (Experimental in v1.6-BETA)

The experimental xAPI Realtime Tracking implements basic LRS Statement API functionality.

This allows an [Activity Provider (AP)](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-About.md#def-activity-provider) to send statements for External Content Activity items, that can be used to set the Moodle Viewed status and External Completed status for the item.

The received statements are not saved to Moodle, but parsed as received

The Statement [Object Id](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#acturi) is used to identify the course and External Content Activity Item, the Moodle [Course ID Number](https://docs.moodle.org/310/en/Course_settings#Course_ID_number) needs to be set to this value.

The Statement [Actor Account Name](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#inversefunctional) is used to identify the Moodle User using their Moodle [Username](https://docs.moodle.org/310/en/Add_a_new_user#Username).

Any Statement [Verb Id](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#verb) received is used to set the Viewed status of the Activity. As Moodle does not support setting the timestamp for the Viewed event it will be the time that the statement was processed by the module.

If the Statement [Verb Id](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#verb) is one of those configured to indicate completion (Defaults are: http://adlnet.gov/expapi/verbs/completed and http://adlnet.gov/expapi/verbs/passed) the Activity Item "Completed Externally" value is set to true.


### What has been implemented
|Resource|HTTP Methods|Comments|
|------- |------------|-----------|
|[Statements Resource](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#stmtres)|GET, PUT, POST|This is WRITE ONLY and so supports POST, PUT only. GET requests return a "null" response so as to be "valid" but the functionality is not implemented. The JSON statements received are parsed using [TinCan PHP](https://github.com/RusticiSoftware/TinCanPHP), and used to set the viewed status, completion status and score for the content if available.|
|[About Resource](https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#aboutresource)|GET|This returns tha xAPI version supported which for this is 1.0.0|
<br>

### Setup Activity Settings
|Name|Setting|Description|
|-----|-------|-----------|
|Enable xAPI LRS basic support|xapienable|Enable the basic xAPI support, if disabled all calls to the xAPI endpoint return a 401 Unauthorized status. The default is enabled.|
|xAPI LRS Username|xapiusername|Configure the username which is used for basic authentication of the basic LRS endpoint. The default is a random string, setup when module is installed.|
|xAPI LRS Password|xapipassword|Configure the password which is used for basic authentication of the basic LRS endpoint. The default is a random string, setup when module is installed.|
|List of completion xAPI verbs|xapicompletionverbs|Set the xAPI verbs that will be used to indicate the content has been "completed", if a verb that matches one of these values is received the completed externally flag is updated. Any other verb simply sets the viewed flag. The defaults are http://adlnet.gov/expapi/verbs/completed and http://adlnet.gov/expapi/verbs/passed|

<br>

### Setup Activity Provider
|Setup|Description|
|------- |-----------|
|LRS Endpoint|In your activity provider you need to configure the path to this modules LRS endpoint. This will be https://{moodlehostname}/mod/externalcontent/lrs/index.php|
|Authentication|The modules LRS only supports Basic Authentication and so you will need to use the username/password you configured above.|


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
