<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\node\NodeInterface;

/**
 * Functional tests for LocalGovDrupal install profile.
 */
class ViewsPageExtenderTest extends BrowserTestBase {

  use NodeCreationTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'localgov_core',
    'localgov_core_views_page_header_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable localgov_page_header_display_extender plugin.
    // @todo do this in the module install.
    $config = \Drupal::service('config.factory')->getEditable('views.settings');
    $display_extenders = $config->get('display_extenders') ?: [];
    $display_extenders[] = 'localgov_page_header_display_extender';
    $config->set('display_extenders', $display_extenders);
    $config->save();

    $this->drupalPlaceBlock('localgov_page_header_block');
  }

  /**
   * Test block display.
   */
  public function testViewWithPageHeadeExtender() {

    // Check node title and summary display on a page.
    $this->createContentType(['type' => 'page']);

    $node = [];
    for ($i = 1; $i <= 10; $i++) {
      $node[] = $this->createNode([
        'type' => 'page',
        'title' => 'page ' . $i . ' title',
        'status' => NodeInterface::PUBLISHED,
        'created' => strtotime('Now -' . (10 - $i) . ' days'),
      ]);
    }

    $first_node = reset($node);
    $last_node = end($node);
    $site_name = \Drupal::config('system.site')->get('name');

    $this->drupalGet('/recent-content');
    $this->assertSession()->pageTextContains('The most recent 10 pages that have been created on ' . $site_name . ', including ' . $last_node->getTitle());

    $this->drupalGet('/oldest-content');
    $this->assertSession()->pageTextContains('The oldest 10 pages that have been created on ' . $site_name . ', including ' . $first_node->getTitle());

  }

}
