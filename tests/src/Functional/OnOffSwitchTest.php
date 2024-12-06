<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the default blocks mechanism can be turned off.
 */
class OnOffSwitchTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_core',
  ];

  /**
   * Data provider for testOnOffSwitch().
   */
  public static function onOffProvider(): \Generator {
    yield [TRUE, TRUE];
    yield [FALSE, FALSE];
  }

  /**
   * Tests the on/off switch for installing default blocks.
   *
   * If localgov_core.settings.install_default_blocks in config is set to FALSE
   * then no blocks should be installed. Otherwise they should.
   *
   * @dataProvider onOffProvider
   */
  public function testOnOffSwitch($installBlocks, $expectBlocks) {

    \Drupal::configFactory()
      ->getEditable('localgov_core.settings')
      ->set('install_default_blocks', $installBlocks)
      ->save();

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = $this->container->get('module_installer');

    // Install module.
    $this->assertTrue($moduleInstaller->install(['localgov_core_default_blocks_test']));

    $this->drupalGet('<front>');
    if ($expectBlocks) {
      $this->assertSession()->pageTextContains('Block in a good region.');
    }
    else {
      $this->assertSession()->pageTextNotContains('Block in a good region.');
    }
  }

}
