<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for determining whether a URL is internal or external.
 */
class LinkyRealmDeterminator implements LinkyRealmDeterminatorInterface {

  /**
   * Cached domains converted to regex ready for preg_grep().
   *
   * @var string[]|null
   */
  protected $internalRegexes;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new LinkyRealmDeterminator service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal(string $urlString): bool {
    $urlInfo = parse_url($urlString);
    if (!isset($urlInfo['host'])) {
      return FALSE;
    }

    ['host' => $host] = $urlInfo;
    foreach ($this->getInternalRegexes() as $regex) {
      if (preg_match($regex, $host)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get internal domains.
   *
   * @return array
   *   An array of internal domains.
   */
  protected function getDomains(): array {
    $config = $this->configFactory->get('linkyreplacer.settings');
    $domains = $config->get('internal_patterns') ?: '';
    return array_filter(preg_split('/\R/', $domains));
  }

  /**
   * Get domains in regex form.
   *
   * @return array
   *   An array of domains in regex form.
   */
  protected function getInternalRegexes(): array {
    if (isset($this->internalRegexes)) {
      return $this->internalRegexes;
    }
    $this->internalRegexes = [];

    $domains = $this->getDomains();
    $domains = array_map(function ($domain): string {
      $domain = preg_quote($domain);
      // Replace the escaped asterisk character with regex compatible wildcard.
      $domain = str_replace('\*', '.*', $domain);
      // Regexify.
      $domain = '/^' . $domain . '$/';
      return $domain;
    }, $domains);

    $this->internalRegexes = $domains;
    return $this->internalRegexes;
  }

}
