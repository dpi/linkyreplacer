<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\linky\LinkyInterface;

/**
 * Utility for dealing with Linky entities.
 */
class LinkyEntityUtility implements LinkyEntityUtilityInterface {

  /**
   * Linky storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkyStorage;

  /**
   * Service for determining whether a URL is internal or external.
   *
   * @var \Drupal\linkyreplacer\LinkyRealmDeterminatorInterface
   */
  protected $realmDeterminator;

  /**
   * LinkyEntityUtility constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\linkyreplacer\LinkyRealmDeterminatorInterface $realmDeterminator
   *   Service for determining whether a URL is internal or external.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LinkyRealmDeterminatorInterface $realmDeterminator) {
    $this->linkyStorage = $entityTypeManager->getStorage('linky');
    $this->realmDeterminator = $realmDeterminator;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkyByHref(string $href): ?LinkyInterface {
    // It is an anchor link so don't process.
    if (strpos($href, '#') === 0) {
      throw new \InvalidArgumentException();
    }
    // It is a 'tel:' link so don't process.
    if (strpos($href, 'tel:') === 0) {
      throw new \InvalidArgumentException();
    }
    // It is a 'mailto:' link so don't process.
    if (strpos($href, 'mailto:') === 0) {
      throw new \InvalidArgumentException();
    }

    if ($this->realmDeterminator->isInternal($href)) {
      throw new \InvalidArgumentException();
    }

    // It is already a link entity.
    if (preg_match('#/admin/content/linky/(?<entity_id>\d+)#', $href, $matches)) {
      if ($linky = $this->getLinkyById((int) $matches['entity_id'])) {
        return $linky;
      }

      // Linkys should not be made into a Linky.
      throw new \InvalidArgumentException();
    }

    if (!UrlHelper::isExternal($href)) {
      // Internal links cannot be Linky.
      // This check must be *after* the linky path check above.
      throw new \InvalidArgumentException();
    }

    return $this->getLinkyByUri($href);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkyByUri(string $uri): ?LinkyInterface {
    $ids = $this->linkyStorage->getQuery()
      ->condition('link.uri', $uri)
      ->execute();
    if ($ids) {
      $id = reset($ids);
      return $this->getLinkyById((int) $id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createLinky(string $uri, string $title): LinkyInterface {
    if (mb_strlen($uri) > 2048) {
      throw new \InvalidArgumentException('URL is too long');
    }

    $link = $this->linkyStorage->create([
      'link' => [
        'uri' => $uri,
        'title' => mb_substr($title, 0, 255),
      ],
    ]);
    $link->save();
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkyById(int $id): ?LinkyInterface {
    return $this->linkyStorage->load($id);
  }

}
