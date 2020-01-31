<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer;

use Drupal\Component\Utility\Html;
use Psr\Log\LoggerInterface;

/**
 * Link Replacer.
 *
 * Replaces href of <a> elements with Linky canonical paths if applicable.
 */
class LinkyReplacerLinkReplacer implements LinkyReplacerLinkReplacerInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Utility for dealing with Linky entities.
   *
   * @var \Drupal\linkyreplacer\LinkyEntityUtilityInterface
   */
  protected $linkyUtility;

  /**
   * LinkyReplacerLinkReplacer constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\linkyreplacer\LinkyEntityUtilityInterface $linkyUtility
   *   Utility for dealing with Linky entities.
   */
  public function __construct(LoggerInterface $logger, LinkyEntityUtilityInterface $linkyUtility) {
    $this->logger = $logger;
    $this->linkyUtility = $linkyUtility;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceHrefWithLinky(string $value): string {
    $dom = Html::load($value);

    // An array containing arrays with keys:
    // - 0: Href value.
    // - 1: <a> tag plain text.
    // - 2: xpath to the <a> element.
    $elements = [];
    foreach ($dom->getElementsByTagName('a') as $node) {
      assert($node instanceof \DOMElement);
      if ($node->hasAttribute('href')) {
        $elements[] = [
          $node->getAttribute('href'), $node->textContent, $node->getNodePath(),
        ];
      }
    }

    // Map of old hrefs to Linky, if href is not present then it shouldn't be
    // transformed into a Linky.
    /** @var string[] $newHrefs */
    $newHrefs = [];

    // Dedupe HREFs.
    $hrefs = array_unique(array_column($elements, 0));
    foreach ($hrefs as $elementsKey => $href) {
      try {
        $linky = $this->linkyUtility->getLinkyByHref($href);
      }
      catch (\InvalidArgumentException $e) {
        // This HREF will not be made into a Linky.
        continue;
      }

      if (!isset($linky)) {
        $title = $elements[$elementsKey][1];
        $title = !empty($title) ? $title : $href;
        try {
          $linky = $this->linkyUtility->createLinky($href, $title);
        }
        catch (\InvalidArgumentException $e) {
          $this->logger->debug('Could not create link for @title @href: @exception', [
            '@title' => $title,
            '@href' => $href,
            '@exception' => $e->getMessage(),
          ]);
          continue;
        }
      }

      try {
        $newHrefs[$href] = '/' . $linky->toUrl()->getInternalPath();
      }
      catch (\InvalidArgumentException $e) {
        $this->logger->debug('Could not get internal path for Linky created from @href', ['@href' => $href]);
        continue;
      }
    }

    // Filter out elements that wont get a Linky.
    $elements = array_filter($elements, function ($element) use ($newHrefs) {
      [$href] = $element;
      return isset($newHrefs[$href]);
    });

    if (count($elements) === 0) {
      // Dont bother running the serializer.
      return $value;
    }

    // Replace HREF in all <a> elements.
    $xpath = new \DOMXPath($dom);
    foreach ($elements as [$href,, $position]) {
      $newHref = $newHrefs[$href];
      $node = $xpath->query($position)[0];
      $node->setAttribute('href', $newHref);
    }

    return Html::serialize($dom);
  }

}
