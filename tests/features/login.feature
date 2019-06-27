@api @casMockServer
Feature: CAS authentication
  In order to access multiple applications using a single set of credentials
  As the user of a web application
  I need to be able to authenticate using the CAS single sing-on service

  Scenario: Log in and log out using CAS
    Given CAS users:
      | Username    | E-mail                            | Password           | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu    | Qwerty098          | Chuck      | Norris    |
      | jb007       | 007@mi6.eu                        | shaken_not_stirred | James      | Bond      |
      | lissa       | Lisbeth.SALANDER@ext.ec.europa.eu | dragon_tattoo      | Lisbeth    | Salander  |

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
    Then I should see "texasranger@chucknorris.com.eu"
