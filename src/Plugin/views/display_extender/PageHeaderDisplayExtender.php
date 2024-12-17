<?php

namespace Drupal\localgov_core\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Localgov page header display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "localgov_page_header_display_extender",
 *   title = @Translation("Page header display extender"),
 *   help = @Translation("Page header settings for this view."),
 *   no_ui = FALSE
 * )
 */
class PageHeaderDisplayExtender extends DisplayExtenderPluginBase {

  use StringTranslationTrait;

  /**
   * The first row tokens on the style plugin.
   *
   * @var array
   */
  protected static $firstRowTokens;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\localgov_core\Plugin\views\display_extender\PageHeaderDisplayExtender */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['lede'] = ['default' => ''];
    $options['tokenize'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {

    if ($form_state->get('section') == 'page_header') {
      $form['#title'] .= $this->t('The page header summary');

      // Build/inject the Metatag form.
      $form['lede'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Lede'),
        '#description' => $this->t('Summary displayed under the page title.'),
        '#default_value' => $this->options['lede'],
      ];
      $this->tokenForm($form['page_header'], $form_state);
    }
  }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('section') == 'page_header') {
      // Process submitted metatag values and remove empty tags.
      $page_header_values = $form_state->cleanValues()->getValues();
      $this->options['tokenize'] = $page_header_values['tokenize'] ?? FALSE;
      unset($page_header_values['tokenize']);
      $this->options['lede'] = $page_header_values['lede'];
    }
  }

  /**
   * Verbatim copy of TokenizeAreaPluginBase::tokenForm().
   */
  public function tokenForm(&$form, FormStateInterface $form_state): void {
    $form['tokenize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use replacement tokens from the first row'),
      '#default_value' => $this->options['tokenize'],
    ];

    // Get a list of the available fields and arguments for token replacement.
    $options = [];
    $optgroup_arguments = (string) new TranslatableMarkup('Arguments');
    $optgroup_fields = (string) new TranslatableMarkup('Fields');
    foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
      $options[$optgroup_fields]["{{ $field }}"] = $handler->adminLabel();
    }

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
    }

    if (!empty($options)) {
      $form['tokens'] = [
        '#type' => 'details',
        '#title' => $this->t('Replacement patterns'),
        '#open' => TRUE,
        '#id' => 'edit-options-token-help',
        '#states' => [
          'visible' => [
            ':input[name="options[tokenize]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['tokens']['help'] = [
        '#markup' => '<p>' . $this->t('The following tokens are available. You may use Twig syntax in this field.') . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = [];
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $form['tokens'][$type]['tokens'] = [
            '#theme' => 'item_list',
            '#items' => $items,
          ];
        }
      }
    }

    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options): void {
    $categories['page_header'] = [
      'title' => $this->t('Page header'),
      'column' => 'second',
    ];
    $options['page_header'] = [
      'category' => 'page_header',
      'title' => $this->t('Page header'),
      'value' => $this->options['lede'] ? $this->t('Custom lede') : $this->t('No lede'),
    ];
  }

  /**
   * Get the lede for display.
   *
   * @param bool $raw
   *   Flag if to return raw (untokenised) output.
   *
   * @return string
   *   Lede for display.
   */
  public function getLede(bool $raw = FALSE) : string {
    $view = $this->view;
    $lede = '';

    if (!empty($this->options['lede'])) {
      $lede = $this->options['lede'];
    }

    if ($this->options['lede'] && !$raw) {
      if (!empty(self::$firstRowTokens[$view->current_display])) {
        self::setFirstRowTokensOnStylePlugin($view, self::$firstRowTokens[$view->current_display]);
      }
      // This is copied from TokenizeAreaPluginBase::tokenizeValue().
      $style = $view->getStyle();
      $lede = $style->tokenizeValue($lede, 0);
      $lede = $this->globalTokenReplace($lede);
    }

    return $lede;
  }

  /**
   * Store first row tokens on the class.
   *
   * The function metatag_views_metatag_route_entity() loads the View fresh, to
   * avoid rebuilding and re-rendering it, preserve the first row tokens.
   */
  public function setFirstRowTokens(array $first_row_tokens): void {
    self::$firstRowTokens[$this->view->current_display] = $first_row_tokens;
  }

  /**
   * Set the first row tokens on the style plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $first_row_tokens
   *   The first row tokens.
   */
  public static function setFirstRowTokensOnStylePlugin(ViewExecutable $view, array $first_row_tokens): void {
    $style = $view->getStyle();
    self::getFirstRowTokensReflection($style)->setValue($style, [$first_row_tokens]);
  }

  /**
   * Get the first row tokens from the style plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   *
   * @return array
   *   The first row tokens.
   */
  public static function getFirstRowTokensFromStylePlugin(ViewExecutable $view): array {
    $style = $view->getStyle();
    return self::getFirstRowTokensReflection($style)->getValue($style)[0] ?? [];
  }

  /**
   * Get the first row tokens for this Views object iteration.
   *
   * @param \Drupal\views\Plugin\views\style\StylePluginBase $style
   *   The style plugin used for this request.
   *
   * @return \ReflectionProperty
   *   The rawTokens property.
   */
  protected static function getFirstRowTokensReflection(StylePluginBase $style): \ReflectionProperty {
    $r = new \ReflectionObject($style);
    $p = $r->getProperty('rowTokens');
    $p->setAccessible(TRUE);
    return $p;
  }

}
