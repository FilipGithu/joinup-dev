@api @eif_community @group-b
Feature:
  As the owner of the EIF Toolbox
  in order to promote solutions we want to recommend
  I need to be able to present the solutions and the recommendations in the EIF toolbox.

  Scenario: The recommendations page lists the recommendations links.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF recommendations"

    # Sample check some links.
    And I should see the following links:
      | Recommendation 1 \| Underlying Principle 1: subsidiarity and proportionality |
      | Recommendation 2 \| Underlying Principle 2: openess                          |
      | Recommendation 3 \| Underlying Principle 2: openess                          |
      | Recommendation 4 \| Underlying Principle 2: openess                          |
      | Recommendation 5 \| Underlying Principle 3: transparency                     |
      | Recommendation 6 \| Underlying Principle 4: reusability                      |
      | Recommendation 7 \| Underlying Principle 4: reusability                      |

  Scenario: Recommendations overview and each recommendation should show the EIF Toolbox header
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF Toolbox"

    When I click "Recommendation 1 | Underlying Principle 1: subsidiarity and proportionality"
    Then I should see the heading "EIF Toolbox"
