@mod @mod_booking @booking_addinstancefromprofile
Feature: Auto-create booking option requires proper capability
  In order to control who can automatically create booking options
  As an admin
  I need to ensure only users with booking:addinstancefromprofile capability can autocreate

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname   | name             |
      | text     | institution | Institution Type |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber | institution | profile_field_institution |
      | teacher1 | Teacher   | One      | teacher1@example.com | T1       | University  | University                |
      | student1 | Student   | One      | student1@example.com | S1       | University  | University                |
      | manager1 | Manager   | One      | manager1@example.com | M1       | University  | University                |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | manager1 | C1     | editingteacher |
      | manager1 | C1     | manager        |
    And the following "activities" exist:
      | activity | course | name                | intro                    | bookingmanager | eventtype | Default view for booking options |
      | booking  | C1     | Auto Create Booking | Booking with auto-create | manager1       | Webinar   | All bookings                     |
    And I create booking option "Template Option" in "Auto Create Booking"

  @javascript
  Scenario: Teacher with capability can auto-create booking option
    # First create template from existing option
    Given I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I follow "Auto Create Booking"
    And I should see "Template Option" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Settings" "icon" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Book other users" "link" in the ".allbookingoptionstable_r1" "css_element"
    And I navigate to "Save booking option as template" in current page administration
    # Now configure autcractive settings
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Enable" "checkbox"
    And I wait "1" seconds
    And I set the field "Custom profile field to check" to "institution"
    And I set the field "Custom profile field value to check" to "University"
    And I set the field "Option template" to "Template Option"
    And I click on "Save and display" "button"
    And I log out
    # Now test that teacher with capability can auto-create
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    Then I should see "University - Teacher One"
    And I log out

  @javascript
  Scenario: Student without capability cannot auto-create booking option
    # First create template from existing option
    Given I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I follow "Auto Create Booking"
    And I should see "Template Option" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Settings" "icon" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Book other users" "link" in the ".allbookingoptionstable_r1" "css_element"
    And I navigate to "Save booking option as template" in current page administration
    # Now configure autcractive settings
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Enable" "checkbox"
    And I wait "1" seconds
    And I set the field "Custom profile field to check" to "institution"
    And I set the field "Custom profile field value to check" to "University"
    And I set the field "Option template" to "Template Option"
    And I click on "Save and display" "button"
    And I log out
    # Test that student without capability cannot auto-create
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    Then I should not see "University - Student One"
    And I log out

  @javascript
  Scenario: Removing capability prevents auto-creation
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber | institution | profile_field_institution |
      | teacher4 | Teacher   | Four     | teacher4@example.com | T4       | University  | University                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher4 | C1     | editingteacher |
    And the following "permission overrides" exist:
      | capability                         | permission | role           | contextlevel | reference |
      | mod/booking:addinstancefromprofile | Prevent    | editingteacher | Course       | C1        |
    # First create template from existing option
    And I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I follow "Auto Create Booking"
    And I should see "Template Option" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Settings" "icon" in the ".allbookingoptionstable_r1" "css_element"
    And I click on "Book other users" "link" in the ".allbookingoptionstable_r1" "css_element"
    And I navigate to "Save booking option as template" in current page administration
    # Now configure autcractive settings
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Enable" "checkbox"
    And I wait "1" seconds
    And I set the field "Custom profile field to check" to "institution"
    And I set the field "Custom profile field value to check" to "University"
    And I set the field "Option template" to "Template Option"
    And I click on "Save and display" "button"
    And I log out
    # Test that teacher with prevented capability cannot auto-create
    When I log in as "teacher4"
    And I am on "Course 1" course homepage
    And I follow "Auto Create Booking"
    Then I should not see "University - Teacher Four"
    And I log out
