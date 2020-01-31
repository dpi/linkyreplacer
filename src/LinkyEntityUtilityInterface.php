<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

use Drupal\linky\LinkyInterface;

/**
 * Interface for Linky Entity Utility.
 */
interface LinkyEntityUtilityInterface {

  /**
   * Gets the Linky for given href.
   *
   * @param string $href
   *   The href. If the href is converted to Linky then the original href is
   *   updated.
   *
   * @return \Drupal\linky\LinkyInterface|null
   *   Linky for entity, or NULL if no Linky exists for this uri.
   *
   * @throws \InvalidArgumentException
   *   If the HREF passed is not a potential candidate to be made into a Linky.
   */
  public function getLinkyByHref(string $href): ?LinkyInterface;

  /**
   * Creates a Linky for given URI and label.
   *
   * @param string $uri
   *   The link uri.
   * @param string $title
   *   The link title.
   *
   * @return \Drupal\linky\LinkyInterface
   *   The created Linky entity.
   *
   * @throws \InvalidArgumentException
   *   If there was a problem with the URI or title.
   */
  public function createLinky(string $uri, string $title): LinkyInterface;

  /**
   * Gets Linky for a given ID.
   *
   * @param int $id
   *   The link ID.
   *
   * @return \Drupal\linky\LinkyInterface|null
   *   Linky, or NULL if no linky exists for this uri.
   */
  public function getLinkyById(int $id): ?LinkyInterface;

  /**
   * Gets Linky for a given URI.
   *
   * @param string $uri
   *   The link URI.
   *
   * @return \Drupal\linky\LinkyInterface|null
   *   Linky, or NULL if no Linky exists for this uri.
   */
  public function getLinkyByUri(string $uri): ?LinkyInterface;

}
