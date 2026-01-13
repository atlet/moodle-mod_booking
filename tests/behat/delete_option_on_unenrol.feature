@mod @mod_booking @mod_booking_delete_option_on_unenrol
Feature: Automatically delete booking option when sole teacher is unenrolled
  In order to keep booking options clean
  As a teacher or manager
  I need booking options to be deleted when the only teacher is unenrolled if the setting is enabled

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | admin1   | Admin     | 1        | admin1@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | admin1   | C1     | manager        |
    And the following "activities" exist:
      | activity | course | name       | intro             | bookingmanager | deleteoptionunenrol |
      | booking  | C1     | My booking | Test description  | admin1         | 0                   |
      | booking  | C1     | Booking 1  | Delete enabled    | admin1         | 1                   |

  @javascript
  Scenario: Option preserved when setting is disabled (default)
    Given the following "mod_booking > options" exist:
      | booking    | text     | course | description | teacher  |
      | My booking | Option 1 | C1     | Desc 1      | teacher1 |
    And I log in as "admin1"
    
    # Unenrol teacher1
    When I am on "Course 1" course homepage
    And I navigate to "Participants" in current page administration
    And I click on "Unenrol" "icon" in the "Teacher 1" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I wait until the page is ready

    # Verify option still exists
    And I am on "Course 1" course homepage
    And I follow "My booking"
    Then I should see "Option 1"

  @javascript
  Scenario: Option deleted when setting enabled and user is sole teacher
    Given the following "mod_booking > options" exist:
      | booking   | text     | course | description | teacher  |
      | Booking 1 | Option 2 | C1     | Desc 2      | teacher1 |
    And I log in as "admin1"
    
    # Unenrol teacher1
    When I am on "Course 1" course homepage
    And I navigate to "Participants" in current page administration
    And I click on "Unenrol" "icon" in the "Teacher 1" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I wait until the page is ready

    # Verify option is deleted
    And I am on "Course 1" course homepage
    And I follow "Booking 1"
    Then I should not see "Option 2"

  @javascript
  Scenario: Option preserved when setting enabled but user is not sole teacher
    Given the following "mod_booking > options" exist:
      | booking   | text     | course | description | teacher            |
      | Booking 1 | Option 3 | C1     | Desc 3      | teacher1, teacher2 |
    And I log in as "admin1"
    
    # Unenrol teacher1
    When I am on "Course 1" course homepage
    And I navigate to "Participants" in current page administration
    And I click on "Unenrol" "icon" in the "Teacher 1" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I wait until the page is ready

    # Verify option still exists (not deleted since teacher2 remains)
    And I am on "Course 1" course homepage
    And I follow "Booking 1"
    Then I should see "Option 3"
