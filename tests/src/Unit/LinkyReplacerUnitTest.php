<?php

namespace Drupal\Tests\linkyreplacer\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\linky\LinkyInterface;
use Drupal\linkyreplacer\LinkyEntityUtility;
use Drupal\linkyreplacer\LinkyEntityUtilityInterface;
use Drupal\linkyreplacer\LinkyRealmDeterminator;
use Drupal\linkyreplacer\LinkyReplacerLinkReplacer;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Linky Replacer tests.
 *
 * @group linkyreplacer
 */
class LinkyReplacerUnitTest extends UnitTestCase {

  /**
   * Basic test of replacement.
   */
  public function testReplacement() {
    $url = $this->createMock(Url::class);
    $url->expects($this->once())
      ->method('getInternalPath')
      ->willReturn('admin/content/linky/123');

    $linky = $this->createMock(LinkyInterface::class);
    $linky->expects($this->once())
      ->method('toUrl')
      ->willReturn($url);

    $linkyUtility = $this->createMock(LinkyEntityUtilityInterface::class);
    $linkyUtility->expects($this->once())
      ->method('getLinkyByHref')
      ->with('http://example.com/')
      ->willReturn($linky);

    $logger = $this->createMock(LoggerInterface::class);
    $replacer = new LinkyReplacerLinkReplacer($logger, $linkyUtility);
    $text = '<strong><a href="http://example.com/">Hello world</a></strong>';
    $result = $replacer->replaceHrefWithLinky($text);
    $this->assertEquals('<strong><a href="/admin/content/linky/123">Hello world</a></strong>', $result);
  }

  /**
   * Test anchor links (#) arn't converted to Linky.
   *
   * @param string $href
   *   HREF for testing.
   * @param bool $shouldConsiderInternal
   *   Whether the HREF should be considered internal.
   *
   * @dataProvider providerNotLinkified
   */
  public function testNotLinkified(string $href, bool $shouldConsiderInternal = FALSE) {
    $domains = $this->createMock(ImmutableConfig::class);
    $domains->expects($shouldConsiderInternal ? $this->once() : $this->never())
      ->method('get')
      ->with('internal_patterns')
      ->willReturn("*.localhost\rFoo");

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($shouldConsiderInternal ? $this->once() : $this->never())
      ->method('get')
      ->with('linkyreplacer.settings')
      ->willReturn($domains);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $realmDeterminator = new LinkyRealmDeterminator($configFactory);
    $linkyUtility = $this
      ->getMockBuilder(LinkyEntityUtility::class)
      ->setConstructorArgs([$entityTypeManager, $realmDeterminator])
      // We want to use original getLinkyByHref().
      ->setMethods(['getLinkyByUri', 'createLinky', 'getLinkyById'])
      ->getMock();

    // Make sure creation is not called.
    $linkyUtility->expects($this->never())
      ->method('createLinky');

    $logger = $this->createMock(LoggerInterface::class);
    $replacer = new LinkyReplacerLinkReplacer($logger, $linkyUtility);
    $text = '<strong><a href="' . $href . '">Hello world</a></strong>';
    $result = $replacer->replaceHrefWithLinky($text);

    // Nothing should change.
    $this->assertEquals($text, $result);
  }

  /**
   * Data provider for testNotLinkified.
   *
   * @return array
   *   Data for testing.
   */
  public function providerNotLinkified(): array {
    return [
      ['#hello-world'],
      ['tel:1234'],
      ['mailto:john@example.com'],
      ['/hello/world'],
      ['/hello'],
      ['/admin/content/linky/123456'],
      ['http://foobar.localhost', TRUE],
    ];
  }

  /**
   * Basic test of replacement.
   *
   * We ensure the first created link gets the innertext of the first <a>
   * element and each unique link is only created once each.
   */
  public function testMultipleSameHref() {
    $linkyUtility = $this->createMock(LinkyEntityUtilityInterface::class);
    $linkyUtility->expects($this->exactly(2))
      ->method('getLinkyByHref')
      ->willReturn(NULL);

    $url = $this->createMock(Url::class);
    $url->expects($this->once())
      ->method('getInternalPath')
      ->willReturn('admin/content/linky/1');
    $linky = $this->createMock(LinkyInterface::class);
    $linky->expects($this->once())
      ->method('toUrl')
      ->willReturn($url);

    $url = $this->createMock(Url::class);
    $url->expects($this->once())
      ->method('getInternalPath')
      ->willReturn('admin/content/linky/2');
    $linky2 = $this->createMock(LinkyInterface::class);
    $linky2->expects($this->once())
      ->method('toUrl')
      ->willReturn($url);

    $linkyUtility->expects($this->any())
      ->method('createLinky')
      ->willReturnMap([
        ['http://example.com/1', 'Link 1', $linky],
        ['http://example.com/2', 'Link 2', $linky2],
      ]);

    $logger = $this->createMock(LoggerInterface::class);
    $replacer = new LinkyReplacerLinkReplacer($logger, $linkyUtility);
    $result = $replacer->replaceHrefWithLinky('
    <a href="http://example.com/1">Link 1</a>
    <a href="http://example.com/2">Link 2</a>
    <a href="http://example.com/1">Link 3</a>
    ');
    $this->assertEquals('
    <a href="/admin/content/linky/1">Link 1</a>
    <a href="/admin/content/linky/2">Link 2</a>
    <a href="/admin/content/linky/1">Link 3</a>
    ', $result);
  }

}
