Feature: External links report
  As a website user
  I want to use the external links report

  Background:
    Given the "group" "EDITOR group" has permissions "CMS_ACCESS_LeftAndMain"
    # Need to use single quotes rather than escaped double quotes when defining the fixture otherwise
    # it'll end up saved as &quot; and the hyperlink will be wrong
    # When the page is published it should be converted by tinymce to double quotes
    Given a "page" "My page" has the "Content" "<p>My <a href='http://fsdjoifidsohfiohfsoifhiodshfhdosi.com'>link</a> content</p>"

  Scenario: Operate the external links report
    Given I am logged in with "ADMIN" permissions

    # Publish page
    When I go to "/admin/pages"
    And I follow "My page"
    And I press the "Publish" button

    # Run report
    When I go to "/admin/reports"
    And I follow "External broken links"
    And I press the "Create new report" button

    # Run queuedjob, new job will be the first row
    When I go to "/admin/queuedjobs"
    When I click on the ".gridfield-button-jobexecute" element
    And I wait for 15 seconds

    # Assert report
    When I go to "/admin/reports"
    And I follow "External broken links"
    Then I should see "http://fsdjoifidsohfiohfsoifhiodshfhdosi.com"
    And I should see "My page"
