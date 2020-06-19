@mod @mod_externalcontent
Feature: Configure externalcontent appearance
  In order to change the appearance of the externalcontent resource
  As an admin
  I need to configure the externalcontent appearance settings

  Background:
    Given the following "courses" exist:
      | shortname | fullname   |
      | C1        | Course 1 |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | externalcontent     | ExternalContentName1  | ExternalContentDesc1  | C1     | EXTERNALCONTENT1    |
    And I log in as "admin"

  @javascript
  Scenario: Display and hide the external content name
    Given I am on "Course 1" course homepage
    When I follow "ExternalContentName1"
    Then I should not see "ExternalContentName1" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display External content name" "checkbox"
    And I press "Save and display"
    Then I should see "ExternalContentName1" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display External content name" "checkbox"
    And I press "Save and display"
    Then I should not see "ExternalContentName1" in the "region-main" "region"

  @javascript
  Scenario: Display and hide the external content description
    Given I am on "Course 1" course homepage
    When I follow "ExternalContentName1"
    Then I should not see "ExternalContentDesc1" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display External content description" "checkbox"
    And I press "Save and display"
    Then I should see "ExternalContentDesc1" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display External content description" "checkbox"
    And I press "Save and display"
    Then I should not see "ExternalContentDesc1" in the "region-main" "region"

  @javascript
  Scenario: Display and hide the last modified date
    Given I am on "Course 1" course homepage
    When I follow "ExternalContentName1"
    Then I should see "Last modified:" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display last modified date" "checkbox"
    And I press "Save and display"
    Then I should not see "Last modified:" in the "region-main" "region"
    And I navigate to "Edit settings" in current page administration
    And I follow "Appearance"
    When I click on "Display last modified date" "checkbox"
    And I press "Save and display"
    Then I should see "Last modified:" in the "region-main" "region"
