@api @tallinn
Feature:
  As a moderator I am able to set the access permissions on dashboard data.
  As an anonymous I can access the dashboard data give the access is public.
  As a collection facilitator or moderator I can access the dashboard data.
  
Scenario: Access test.

  Given users:
    | Username | Roles     |
    | Dinesh   |           |
    | Gilfoyle |           |
    | Jared    | moderator |
  And the following collection user membership:
    | collection                      | user     | roles       |
    | Tallinn Ministerial Declaration | Gilfoyle | facilitator |

  Given I am an anonymous user
  And I go to "/admin/config/content/tallinn"
  Then I should see the following error message:
    | error messages                                     |
    | Access denied. You must sign in to view this page. |
  And I go to "/tallinn-dashboard"
  Then I should see the following error message:
    | error messages                                     |
    | Access denied. You must sign in to view this page. |

  Given I am logged in as Dinesh
  And I go to "/admin/config/content/tallinn"
  Then the response status code should be 403
  And I go to "/tallinn-dashboard"
  Then the response status code should be 403

  Given I am logged in as Gilfoyle
  And I go to "/admin/config/content/tallinn"
  Then the response status code should be 403
  And I go to "/tallinn-dashboard"
  Then the response status code should be 200

  Given I am logged in as Jared
  And I go to "/tallinn-dashboard"
  Then the response status code should be 200
  And I go to "/admin/config/content/tallinn"
  Then the response status code should be 200
  And I should see the heading "Tallinn Settings"
  And the radio button "Restricted (only moderators and Tallinn collection facilitators)" from field "Access to the dashboard data" should be selected

  Given I select the radio button "Public"
  When I press "Save configuration"
  Then I should see the following success messages:
    | success messages |
    | Permissions successfully updated. |
  And the radio button "Public" from field "Access to the dashboard data" should be selected

  Given I go to "/tallinn-dashboard"
  Then the response status code should be 200

  Given I am logged in as Gilfoyle
  And I go to "/tallinn-dashboard"
  Then the response status code should be 200

  Given I am logged in as Dinesh
  And I go to "/tallinn-dashboard"
  Then the response status code should be 200

  Given I am an anonymous user
  And I go to "/tallinn-dashboard"
  Then the response status code should be 200

  # Restore the initial setting.
  Given I am logged in as Jared
  When I go to "/admin/config/content/tallinn"
  And I select the radio button "Restricted (only moderators and Tallinn collection facilitators)"
  Then I press "Save configuration"
