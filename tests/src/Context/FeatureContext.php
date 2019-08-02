<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\cas_mock_server\Plugin\Menu\CasLoginMenuLink;
use Drupal\DrupalExtension\Context\RawDrupalContext;

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
    $plugin_definition = [
      'class' => CasLoginMenuLink::class,
      'title' => 'CAS Login',
      'menu_name' => 'footer',
    ];
    $instance = CasLoginMenuLink::create(\Drupal::getContainer(), [], 'cas_mock_server.cas_login', $plugin_definition);
    \Drupal::service('plugin.manager.menu.link')->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
  }

  /**
   * Removes the "CAS login" link from the footer.
   *
   * @AfterScenario @casLoginLink
   */
  public function startMockServer(): void {
    \Drupal::service('plugin.manager.menu.link')->removeDefinition('cas_mock_server.cas_login');
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
