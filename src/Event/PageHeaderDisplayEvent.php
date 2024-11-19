<?php

namespace Drupal\localgov_core\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Event that is fired when displaying the page header.
 */
class PageHeaderDisplayEvent extends Event {

  const EVENT_NAME = 'localgov_core.page_header_display';

  /**
   * Entity associated with the current route.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * View executable associated with the current route.
   *
   * @var \Drupal\views\ViewExecutable|null
   */
  protected $view = NULL;

  /**
   * The page lede override.
   *
   * @var array|string|null
   */
  protected $lede = NULL;

  /**
   * The page title override.
   *
   * @var array|string|null
   */
  protected $title = NULL;

  /**
   * The page sub title override.
   *
   * @var array|string|null
   */
  protected $subTitle = NULL;

  /**
   * Should the page header block be displayed?
   *
   * @var bool
   */
  protected $visibility = TRUE;

  /**
   * Cache tags override.
   *
   * @var array|null
   */
  protected $cacheTags = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity = NULL) {
    // @todo remove the $entity paramater or deprecate it.
    // Since we should use the setters and getters.
    // Or we can mark this class as internal?
    $this->entity = $entity;
  }

  /**
   * Entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getEntity() : ?EntityInterface {
    return $this->entity;
  }

  /**
   * Entity setter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function setEntity(EntityInterface $entity) : void {
    $this->entity = $entity;
  }

  /**
   * View getter.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   The view.
   */
  public function getView() : ?ViewExecutable {
    return $this->view;
  }

  /**
   * View setter.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   */
  public function setView(ViewExecutable $view) {
    $this->view = $view;
  }

  /**
   * Lede getter.
   *
   * @return array|string|null
   *   The lede.
   */
  public function getLede() {
    return $this->lede;
  }

  /**
   * Lede setter.
   *
   * @param array|string|null $lede
   *   The lede.
   */
  public function setLede($lede) {
    $this->lede = $lede;
  }

  /**
   * Title getter.
   *
   * @return array|string|null
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Title setter.
   *
   * @param array|string|null $title
   *   The title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Sub title getter.
   *
   * @return array|string|null
   *   The sub title.
   */
  public function getSubTitle() {
    return $this->subTitle;
  }

  /**
   * Sub title setter.
   *
   * @param array|string|null $sub_title
   *   The sub title.
   */
  public function setSubTitle($sub_title) {
    $this->subTitle = $sub_title;
  }

  /**
   * Visibility getter.
   *
   * @return bool|null
   *   The title.
   */
  public function getVisibility() {
    return $this->visibility;
  }

  /**
   * Visibility setter.
   *
   * @param bool $visibility
   *   The visibility.
   */
  public function setVisibility($visibility) {
    $this->visibility = $visibility;
  }

  /**
   * Cache tags getter.
   *
   * @return array|null
   *   Cache tags array if set.
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * Cache tags setter.
   *
   * @param array $cacheTags
   *   The cache tags.
   */
  public function setCacheTags(array $cacheTags) {
    $this->cacheTags = $cacheTags;
  }

}
