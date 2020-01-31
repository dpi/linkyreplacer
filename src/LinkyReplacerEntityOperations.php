<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 */
class LinkyReplacerEntityOperations implements ContainerInjectionInterface {

  /**
   * Field types to replace links in.
   */
  protected const FIELD_TYPES = [
    'text',
    'text_long',
    'text_with_summary',
  ];

  /**
   * Link replacer.
   *
   * @var \Drupal\linkyreplacer\LinkyReplacerLinkReplacerInterface
   */
  protected $linkReplacer;

  /**
   * Constructs a new LinkyReplacerEntityOperations.
   *
   * @param \Drupal\linkyreplacer\LinkyReplacerLinkReplacerInterface $linkReplacer
   *   Link replacer.
   */
  public function __construct(LinkyReplacerLinkReplacerInterface $linkReplacer) {
    $this->linkReplacer = $linkReplacer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('linkyreplacer.link_replacer')
    );
  }

  /**
   * Implements hook_entity_presave().
   *
   * @see \linkyreplacer_entity_presave()
   */
  public function entityPreSave(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    foreach ($entity as $fieldName => $fieldList) {
      assert($fieldList instanceof FieldItemListInterface);
      if (in_array($fieldList->getFieldDefinition()->getType(), static::FIELD_TYPES, TRUE)) {
        foreach ($fieldList as $item) {
          $item->value = $this->linkReplacer->replaceHrefWithLinky($item->value);
        }
      }
    }
  }

}
