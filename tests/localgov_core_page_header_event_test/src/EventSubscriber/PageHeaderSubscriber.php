<?php

namespace Drupal\localgov_core_page_header_event_test\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\localgov_core\Event\PageHeaderDisplayEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test page header events.
 *
 * @package Drupal\localgov_core_page_header_event_test\EventSubscriber
 */
class PageHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PageHeaderDisplayEvent::EVENT_NAME => ['setPageHeader', 0],
    ];
  }

  /**
   * Set page title and lede.
   */
  public function setPageHeader(PageHeaderDisplayEvent $event) {

    $node = $event->getEntity();

    if (!$node instanceof Node) {
      return;
    }

    // Override title and lede for page1 node content types.
    if ($node->bundle() == 'page1') {
      $event->setTitle('Overridden title');
      $event->setLede('Overridden lede');
    }

    // Hide page header block for page2 content types.
    if ($node->bundle() == 'page2') {
      $event->setVisibility(FALSE);
    }

    // Set lede from summary, and cache tags from the parent for page3 nodes.
    if ($node->bundle() == 'page3') {
      $parent = $node->parent->entity;
      $event->setLede($parent->body->summary);
      $event->setCacheTags(Cache::mergeTags($node->getCacheTags(), $parent->getCacheTags()));
    }

    // Set subtitle from parent, and cache tags from the parent for page4 nodes.
    if ($node->bundle() == 'page4') {
      $parent = $node->parent->entity;
      $event->setSubTitle($parent->title->value);
      $event->setCacheTags(Cache::mergeTags($node->getCacheTags(), $parent->getCacheTags()));
    }
  }

}
