@mod @mod_booking @booking_prevent_overbooking
Feature: Prevent booking overlapping options
  As an administrator I want to prevent users from booking
  Multiple options that have overlapping times
  So that users cannot be double-booked for simultaneous events

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com  | T1       |
      | student1 | Student   | 1        | student1@example1.com | S1       |
      | student2 | Student   | 2        | student2@example2.com | S2       |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

  @javascript
  Scenario: Student can book overlapping options when preventoverbooking is disabled
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | preventoverbooking |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | 0                  |
    And I create booking option "Option Morning 9-12" in "My booking" starting "+1 day 09:00" ending "+1 day 12:00"
    And I create booking option "Option Morning 10-11" in "My booking" starting "+1 day 10:00" ending "+1 day 11:00"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    Then I should see "Book now" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    # Book first option
    And I click on "Book now" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r1" "css_element"
    # Second overlapping option should still be bookable
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Book now" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r2" "css_element"

  @javascript
  Scenario: Student cannot book overlapping options when preventoverbooking is enabled
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | preventoverbooking |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | 1                  |
    And I create booking option "Option Morning 9-12" in "My booking" starting "+1 day 09:00" ending "+1 day 12:00"
    And I create booking option "Option Morning 10-11" in "My booking" starting "+1 day 10:00" ending "+1 day 11:00"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    Then I should see "Book now" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    # Book first option
    And I click on "Book now" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I wait until the page is ready
    And I should see "Booked" in the ".allbookingoptionstable_r1" "css_element"
    # Second overlapping option should NOT be bookable anymore - need to reload page to see updated status
    And I reload the page
    And I wait until the page is ready
    And I should see "Time conflict" in the ".allbookingoptionstable_r2" "css_element"
    And I should not see "Book now" in the ".allbookingoptionstable_r2" "css_element"

  @javascript
  Scenario: Student can book non-overlapping options when preventoverbooking is enabled
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | preventoverbooking |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | 1                  |
    And I create booking option "Option Morning 9-12" in "My booking" starting "+1 day 09:00" ending "+1 day 12:00"
    And I create booking option "Option Afternoon 14-17" in "My booking" starting "+1 day 14:00" ending "+1 day 17:00"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    Then I should see "Book now" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    # Book first option
    And I click on "Book now" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r1" "css_element"
    # Second non-overlapping option should still be bookable
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Book now" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r2" "css_element"

  @javascript
  Scenario: Options without times can always be booked when preventoverbooking is enabled
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | preventoverbooking |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | 1                  |
    And I create booking option "Option Morning 9-12" in "My booking" starting "+1 day 09:00" ending "+1 day 12:00"
    And I create booking option "Option No Times" in "My booking"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    # Book first option with times
    And I click on "Book now" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r1" "css_element"
    # Option without times should still be bookable
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Book now" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r2" "css_element"

  @javascript
  Scenario: Different users can book overlapping options when preventoverbooking is enabled
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | preventoverbooking |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | 1                  |
    And I create booking option "Option Morning 9-12" in "My booking" starting "+1 day 09:00" ending "+1 day 12:00"
    And I create booking option "Option Morning 10-11" in "My booking" starting "+1 day 10:00" ending "+1 day 11:00"
    # Student 1 books first option
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    And I click on "Book now" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r1" "css_element"
    And I log out
    # Student 2 should be able to book overlapping option (different user)
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "My booking"
    And I wait until the page is ready
    Then I should see "Book now" in the ".allbookingoptionstable_r1" "css_element"
    And I should see "Book now" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Book now" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Do you really want to book?" in the ".allbookingoptionstable_r2" "css_element"
    And I click on "Do you really want to book?" "text" in the ".allbookingoptionstable_r2" "css_element"
    And I should see "Booked" in the ".allbookingoptionstable_r2" "css_element"

  @javascript
  Scenario: Teacher can enable preventoverbooking setting via booking instance form
    Given the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "My booking" "link" in the "region-main" "region"
    And I wait until the page is ready
    And I navigate to "Settings" in current page administration
    And I wait until the page is ready
    And I follow "Miscellaneous settings"
    Then I should see "Prevent booking overlapping options"
    And I set the field "Prevent booking overlapping options" to "Yes"
    And I press "Save and display"
    And I wait until the page is ready
    And I navigate to "Settings" in current page administration
    And I follow "Miscellaneous settings"
    And the field "Prevent booking overlapping options" matches value "Yes"
