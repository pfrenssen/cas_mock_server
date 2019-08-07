@api @casMockServer
Feature: CAS authentication
  In order to access multiple applications using a single set of credentials
  As the user of a web application
  I need to be able to authenticate using the CAS single sign-on service

  Scenario: Log in and log out using CAS
    Given CAS users:
      | Username    | E-mail                            | Password           | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu    | Qwerty098          | Chuck      | Norris    |
      | jb007       | 007@mi6.eu                        | shaken_not_stirred | James      | Bond      |
      | lissa       | Lisbeth.SALANDER@ext.ec.europa.eu | dragon_tattoo      | Lisbeth    | Salander  |

    # Navigate to the login form.
    Given I am on the homepage
    And I click "Log in"

    # The CAS Login link will redirect the user to the authentication form
    # of the CAS mock server.
    When I click "CAS Login"
    Then I should see the heading "Login"
    When I fill in "E-mail" with "texasranger@chucknorris.com.eu"
    And I fill in "Password" with "wrong password"
    And I press the "Log in" button
    Then I should see the error message "Unrecognized user name or password."

    # After a successful authentication the user gets redirected back to Drupal.
    When I fill in "Password" with "Qwerty098"
    And I press the "Log in" button
    Then I should see "You have been logged in using CAS."
    And I should see the link "My account"
    And I should see the link "Log out"
    And I should not see the link "Log in"

    # Check that the CAS attributes were used for creating the user account.
    When I click "My account"
    Then I should see the heading "chucknorris"
    When I click "Edit"
    Then the "Email address" field should contain "texasranger@chucknorris.com.eu"

  Scenario: Login with an already linked account.
    Given users:
      | name        | mail                    |
      | chuck_local | chuck_local@example.com |
    Given CAS users:
      | Username    | E-mail                         | Password  | First name | Last name | Local username |
      | chucknorris | texasranger@chucknorris.com.eu | Qwerty098 | Chuck      | Norris    | chuck_local    |

    Given I am on the homepage
    When I click "Log in"
    Then I click "CAS Login"
    And I fill in "E-mail" with "texasranger@chucknorris.com.eu"
    And I fill in "Password" with "Qwerty098"
    When I press the "Log in" button
    Then I should see "You have been logged in using CAS."
    And I should see the link "My account"

    When I click "My account"
    Then I should see the heading "chuck_local"
    When I click "Edit"
    Then the "Email address" field should contain "chuck_local@example.com"

  @casLoginLink
  Scenario Outline: User is redirected back to the page where they logged in.
    Given CAS users:
      | Username | E-mail               | Password |
      | cohen    | cohen@silverhorde.am | Bethan   |
    And I am on "<path>"
    When I click "CAS Login"
    And I fill in "E-mail" with "cohen@silverhorde.am"
    And I fill in "Password" with "Bethan"
    And I press the "Log in" button
    Then I should see "You have been logged in using CAS."
    And I should be on the path "<path>"

    Examples:
      | path                       |
      | /                          |
      | /contact                   |
      | /search/node?keys=treasure |
