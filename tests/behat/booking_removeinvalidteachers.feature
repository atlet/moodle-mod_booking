@mod @mod_booking @booking_removeinvalidteachers
Feature: Remove teachers without valid permissions from booking options

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname    | name              |
      | text     | institution  | Institution Type  |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber | profile_field_institution |
      | teacher1 | Teacher   | Valid    | teacher1@example.com | T1       | University                |
      | teacher2 | Teacher   | Invalid  | teacher2@example.com | T2       | School                    |
      | teacher3 | Teacher   | NoField  | teacher3@example.com | T3       |                           |
      | admin1   | Admin     | 1        | admin1@example.com   | A1       | University                |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | teacher3 | C1     | editingteacher |
      | admin1   | C1     | editingteacher |
      | admin1   | C1     | manager        |
    And the following "activities" exist:
      | activity | course | name               | intro                      | bookingmanager | eventtype | autcractive | autcrprofile | autcrvalue |
      | booking  | C1     | Auto Create Booking | Booking with auto-create  | admin1         | Webinar   | 1           | institution  | University |

  @javascript
  Scenario: Admin can access remove invalid teachers page when autcractive is enabled
    Given I log in as "admin1"
    And I am on "Course 1" course homepage
    When I follow "Auto Create Booking"
    And I navigate to "Remove teachers without permissions" in current page administration
    Then I should see "Remove teachers without permissions"
    And I should see "This feature checks all teachers assigned to booking options"
    And I log out

  @javascript
  Scenario: Regular teacher cannot access remove invalid teachers page
    Given the following "users" exist:
      | username | firstname | lastname | email               | idnumber |
      | student1 | Student   | One      | student1@example.com | S1       |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    Then "Remove teachers without permissions" "link" should not exist in current page administration
    And I log out

  @javascript
  Scenario: Remove invalid teachers page shows teachers without valid profile field
    Given the following "mod_booking > options" exist:
      | booking             | text            | course | description | teacher          |
      | Auto Create Booking | Valid Option    | C1     | Option 1    | teacher1         |
      | Auto Create Booking | Invalid Option  | C1     | Option 2    | teacher2         |
      | Auto Create Booking | No Field Option | C1     | Option 3    | teacher3         |
    And I log in as "admin1"
    And I am on "Course 1" course homepage
    When I follow "Auto Create Booking"
    And I navigate to "Remove teachers without permissions" in current page administration
    Then I should see "The following teachers no longer have the required profile field value"
    And I should see "Teacher Invalid"
    And I should see "Invalid Option"
    And I should see "Teacher NoField"
    And I should see "No Field Option"
    And I should not see "Teacher Valid"
    And I should not see "Valid Option"
    And I log out

  @javascript
  Scenario: Remove only teachers without deleting options
    Given the following "mod_booking > options" exist:
      | booking             | text           | course | description | teacher  |
      | Auto Create Booking | Test Option    | C1     | Option 1    | teacher2 |
    And I log in as "admin1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I navigate to "Remove teachers without permissions" in current page administration
    And I should see "Teacher Invalid"
    And I should see "Test Option"
    When I click on "Remove teachers only" "button"
    Then I should see "Teachers have been successfully removed from their options"
    And I navigate to "Remove teachers without permissions" in current page administration
    And I should see "All teachers have valid permissions. No action needed."
    And I log out

  @javascript
  Scenario: Remove teachers and delete their options
    Given the following "mod_booking > options" exist:
      | booking             | text           | course | description | teacher  |
      | Auto Create Booking | Delete Option  | C1     | Option 1    | teacher3 |
    And I log in as "admin1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I navigate to "Remove teachers without permissions" in current page administration
    And I should see "Teacher NoField"
    And I should see "Delete Option"
    When I click on "Remove teachers and delete their options" "button"
    Then I should see "Teachers and their booking options have been successfully removed"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I should not see "Delete Option"
    And I log out

  @javascript
  Scenario: No invalid teachers message is shown when all teachers are valid
    Given the following "mod_booking > options" exist:
      | booking             | text         | course | description | teacher  |
      | Auto Create Booking | Valid Only   | C1     | Option 1    | teacher1 |
    And I log in as "admin1"
    And I am on "Course 1" course homepage
    When I follow "Auto Create Booking"
    And I navigate to "Remove teachers without permissions" in current page administration
    Then I should see "All teachers have valid permissions. No action needed."
    And I should not see "The following teachers no longer have the required profile field value"
    And I log out

  @javascript
  Scenario: Link is hidden when autcractive is disabled
    Given the following "activities" exist:
      | activity | course | name            | intro               | bookingmanager | autcractive |
      | booking  | C1     | Regular Booking | Regular booking     | admin1         | 0           |
    And I log in as "admin1"
    And I am on "Course 1" course homepage
    When I follow "Regular Booking"
    Then "Remove teachers without permissions" "link" should not exist in current page administration
    And I log out
