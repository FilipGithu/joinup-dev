Feature: User authentication
  In order to protect the integrity of the website
  As a product owner
  I want to make sure users with various roles can only access pages they are authorized to

  Background:
    Given collections:
      | title                       | uri               |
      | Überwaldean Land Eels       | http://drupal.org |

  Scenario: Anonymous user can see the user login page
    Given I am not logged in
    When I visit "user"
    Then I should see the text "Log in"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    But I should not see the text "Log out"
    And I should not see the text "My account"

  Scenario: Anonymous user can access public pages
    Given I am not logged in
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should see the heading "Überwaldean Land Eels"

  Scenario Outline: Anonymous user cannot access restricted pages
    Given I am not logged in
    When I go to "<path>"
    Then I should see the error message "Access denied. You must log in to view this page."

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |
      | rdf_entity/add/collection      |
      | node/add/custom_page           |
      | rdf_entity/add/solution        |

  @api
  Scenario: Authenticated user can access pages they are authorized to
    Given I am logged in as a user with the "authenticated user" role
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should see the heading "Überwaldean Land Eels"

  @api
  Scenario Outline: Authenticated user cannot access site administration
    Given I am logged in as a user with the "authenticated" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/content/rdf              |
      | admin/people                   |
      | admin/structure                |
      | rdf_entity/add/collection      |
      | node/add/custom_page           |
      | rdf_entity/add/solution        |

  @api
  Scenario Outline: Moderator can access pages they are authorized to
    Given I am logged in as a user with the "moderator" role
    Then I visit "<path>"

    Examples:
      | path                           |
      | admin/people                   |
      | admin/content/rdf              |

  @api
  Scenario Outline: Moderator cannot access restricted pages
    Given I am logged in as a user with the "moderator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/structure                |
      | rdf_entity/add/collection      |
      | node/add/custom_page           |
      | rdf_entity/add/solution        |

  @api
  Scenario Outline: Administrator cannot access pages intended for site building and development
    Given I am logged in as a user with the "administrator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |
      | rdf_entity/add/collection      |
