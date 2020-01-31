<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

/**
 * Interface for Link Replacer.
 */
interface LinkyReplacerLinkReplacerInterface {

  /**
   * Replaces links in HTML with Linky URLs.
   *
   * @param string $value
   *   The text field content.
   *
   * @return string
   *   The updated text field content.
   */
  public function replaceHrefWithLinky(string $value): string;

}
