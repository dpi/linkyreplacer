<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

/**
 * Service for determining whether a URL is internal or external.
 */
interface LinkyRealmDeterminatorInterface {

  /**
   * Determines if a URL is an internal URL.
   *
   * @param string $urlString
   *   A URL.
   *
   * @return bool
   *   Whether the URL is a internal URL.
   */
  public function isInternal(string $urlString): bool;

}
