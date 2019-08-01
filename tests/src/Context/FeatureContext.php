<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Generic step definitions used in our feature scenarios.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * The ID of the menu link pointing to the CAS login.
   *
   * This is being tracked so this link can be removed when the scenario ends.
   *
   * @var int
   */
  protected $casLoginLinkId;

  /**
   * Puts a "CAS login" link in the footer.
   *
   * This makes it possible to log in using CAS from any page.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario @casLoginLink
   */
  public function enableCasLoginLink(BeforeScenarioScope $scope): void {
    $menu_link = MenuLinkContent::create([
      'title' => 'CAS Login',
      'link' => ['uri' => 'internal:/caslogin'],
      'menu_name' => 'footer',
    ]);
    $menu_link->save();
    $this->casLoginLinkId = $menu_link->id();
  }

  /**
   * Removes the "CAS login" link from the footer.
   *
   * @AfterScenario @casLoginLink
   */
  public function startMockServer(): void {
    if (!empty($this->casLoginLinkId)) {
      $menu_link = MenuLinkContent::load($this->casLoginLinkId);
      $menu_link->delete();
      $this->casLoginLinkId = NULL;
    }
  }

  /**
   * Asserts that the browser is on the expected path.
   *
   * The Mink extension provides steps to match the current path but they strip
   * off the query arguments. This step will compare the entire path including
   * query arguments.
   *
   * @see \Behat\MinkExtension\Context\MinkContext::assertPageAddress()
   *
   * @Then I should be on the path :path
   */
  public function assertCurrentPath(string $path): void {
    $current_url = $this->getSession()->getCurrentUrl();
    // Get the base URL, removing the port specifier.
    $base_url = preg_replace('/(:\d+)$/', '', $this->getMinkParameter('base_url'));
    $current_path = str_replace($base_url, '', $current_url);
    if ($current_path !== $path) {
      $message = 'Url "%s" does not match expected "%s".';
      throw new \Exception(sprintf($message, $current_path, $path));
    }
  }

}
