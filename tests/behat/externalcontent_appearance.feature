@mod @mod_externalcontent
Feature: Configure externalcontent appearance
  In order to change the appearance of the externalcontent resource
  As an admin
  I need to configure the externalcontent appearance settings

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "activities" exist:
      | activity        | name                 | intro                | course | idnumber         |
      | externalcontent | ExternalContentName1 | ExternalContentDesc1 | C1     | EXTERNALCONTENT1 |

  @javascript
  Scenario Outline: Hide and display page features
    Given I am on the "ExternalContentName1" "externalcontent activity editing" page logged in as admin
    And I expand all fieldsets
    And I set the field "<feature>" to "<value>"
    And I press "Save and display"
    Then I <shouldornot> see "<lookfor>" in the "region-main" "region"

    Examples:
      | feature                              | lookfor              | value | shouldornot |
      | Display External content description | ExternalContentDesc1 | 1     | should      |
      | Display External content description | ExternalContentDesc1 | 0     | should not  |
      | Display last modified date           | Last modified:       | 1     | should      |
      | Display last modified date           | Last modified:       | 0     | should not  |
