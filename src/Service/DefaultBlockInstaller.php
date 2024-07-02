<?php

namespace Drupal\localgov_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Service to install default blocks.
 */
class DefaultBlockInstaller {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Array of regions in each theme.
   *
   * @var array
   */
  protected $themeRegions = [];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileSystemInterface $fileSystem,
    ModuleHandlerInterface $moduleHandler,
    ThemeHandlerInterface $themeHandler,
    ThemeManagerInterface $themeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
    $this->themeManager = $themeManager;
  }

  /**
   * Read the yaml files provided by modules.
   */
  protected function blockDefinitions(string $module): array {

    $modulePath = $this->moduleHandler->getModule($module)->getPath();
    $moduleBlockDefinitionsPath = $modulePath . '/config/localgov';
    $blocks = [];

    if (is_dir($moduleBlockDefinitionsPath)) {
      $files = $this->fileSystem->scanDirectory($moduleBlockDefinitionsPath, '/block\..+\.yml$/');
      foreach ($files as $file) {
        $blocks[] = Yaml::parseFile($moduleBlockDefinitionsPath . '/' . $file->filename);
      }
    }

    return $blocks;
  }

  /**
   * The themes we'll be installing blocks into.
   */
  protected function targetThemes(): array {

    $themes = ['localgov_base', 'localgov_scarfolk'];

    $activeTheme = $this->getActiveThemeName();
    if ($activeTheme && !in_array($activeTheme, $themes)) {
      $themes[] = $activeTheme;
    }

    // Check if the site is being installed. If it is, we can't check if themes
    // exist. We'll assume that localgov_base and localgov_scarfolk are present
    // for new installs.
    if (!InstallerKernel::installationAttempted()) {
      // Don't try to use themes that don't exist.
      foreach ($themes as $i => $theme) {
        if (!$this->themeHandler->themeExists($theme)) {
          unset($themes[$i]);
        }
      }
    }

    return $themes;
  }

  /**
   * Gets the name of the active theme, if there is one.
   */
  protected function getActiveThemeName(): ?string {
    $activeTheme = $this->themeManager->getActiveTheme()->getName();
    if ($activeTheme === 'core') {
      // 'core' is what Drupal returns when there's no themes available.
      // See \Drupal\Core\Theme\ThemeInitialization::getActiveThemeByName().
      // This mainly happens in the installer.
      return NULL;
    }
    return $activeTheme;
  }

  /**
   * Installs the default blocks for the given module.
   */
  public function install(string $module): void {

    $blocks = $this->blockDefinitions($module);

    // Loop over every theme and block definition, so we set up all the blocks
    // in all the relevant themes.
    foreach ($this->targetThemes() as $theme) {
      foreach ($blocks as $block) {

        // If we're not in the installer, verify that the theme we're using has
        // the requested region.
        if (!InstallerKernel::installationAttempted()) {
          if (!$this->themeHasRegion($theme, $block['region'])) {
            continue;
          }
        }

        $block['id'] = $this->sanitiseId($theme . '_' . $block['plugin']);
        $block['theme'] = $theme;

        $this->entityTypeManager
          ->getStorage('block')
          ->create($block)
          ->save();
      }
    }
  }

  /**
   * Replace characters that aren't allowed in config IDs.
   *
   * This is partly based on
   * \Drupal\Core\Block\BlockPluginTrait::getMachineNameSuggestion().
   */
  protected function sanitiseId(string $id): string {

    // Shift to lower case.
    $id = mb_strtolower($id);

    // Limit to alphanumeric chars, dot and underscore.
    $id = preg_replace('@[^a-z0-9_.]+@', '_', $id);

    // Remove non-alphanumeric chars from the beginning and end of the id.
    $id = preg_replace('@^([^a-z0-9]+)|([^a-z0-9]+)$@', '', $id);

    return $id;
  }

  /**
   * Does the given theme have the given region?
   */
  protected function themeHasRegion(string $theme, string $region): bool {
    return in_array($region, $this->themeRegions($theme));
  }

  /**
   * Gets the regions for the given theme.
   */
  protected function themeRegions(string $theme): array {
    if (!isset($this->themeRegions[$theme])) {
      $themeInfo = $this->themeHandler->getTheme($theme);
      if (empty($themeInfo)) {
        $regions = [];
      }
      else {
        $regions = array_keys($themeInfo->info['regions']);
      }
      $this->themeRegions[$theme] = $regions;
    }
    return $this->themeRegions[$theme];
  }

}