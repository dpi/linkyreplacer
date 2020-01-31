<?php

declare(strict_types = 1);

namespace Drupal\Tests\linkyreplacer\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\linky\Entity\Linky;

/**
 * Tests Linky Replacer.
 *
 * @group linkyreplacer
 */
class LinkyReplacerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'linkyreplacer',
    'linky',
    'dynamic_entity_reference',
    'link',
    'text',
    'filter',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('linky');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['linkyreplacer']);
    FieldStorageConfig::create([
      'field_name' => 'testfield',
      'type' => 'text_long',
      'entity_type' => 'entity_test',
    ])->save();
    FieldConfig::create([
      'field_name' => 'testfield',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Basic replacement test.
   */
  public function testReplacement(): void {
    $entity = EntityTest::create();
    $entity->testfield->value = '<a href="http://google.com/">Hello world</a>';
    $entity->save();
    $this->assertEquals('<a href="/admin/content/linky/1">Hello world</a>', $entity->testfield->value);
  }

  /**
   * Basic replacement test.
   */
  public function testMultipleSameHref(): void {
    $entity = EntityTest::create();
    $entity->testfield->value = '<a href="http://example.com/1">Link 1</a><a href="http://example.com/2">Link 2</a><a href="http://example.com/1">Link 3</a>';
    $entity->save();
    $this->assertEquals('<a href="/admin/content/linky/1">Link 1</a><a href="/admin/content/linky/2">Link 2</a><a href="/admin/content/linky/1">Link 3</a>', $entity->testfield->value);

    $linkys = Linky::loadMultiple();
    $this->assertCount(2, $linkys);
    $this->assertEquals('Link 1 (http://example.com/1)', $linkys[1]->label());
    $this->assertEquals('Link 2 (http://example.com/2)', $linkys[2]->label());
  }

  /**
   * Test URLs considered internal are not converted to Linky.
   */
  public function testUrlInternal(): void {
    $this->config('linkyreplacer.settings')->set('internal_patterns', '*.example.com')->save(TRUE);
    $entity = EntityTest::create();
    $entity->testfield->value = '<a href="http://foo.example.com/">Link 1</a>';
    $entity->save();
    $this->assertEquals('<a href="http://foo.example.com/">Link 1</a>', $entity->testfield->value);
    $this->assertCount(0, Linky::loadMultiple());
  }

  /**
   * Test existing Linky entity is re-used.
   */
  public function testLinkyReuse(): void {
    Linky::create([
      'link' => [
        'uri' => 'http://www.example.com/',
        'title' => 'Bleh',
      ],
    ])->save();
    $entity = EntityTest::create();
    $entity->testfield->value = '<a href="http://www.example.com/">Link 1</a>';
    $entity->save();
    $this->assertEquals('<a href="/admin/content/linky/1">Link 1</a>', $entity->testfield->value);
    // No new Linkys are created.
    $this->assertCount(1, Linky::loadMultiple());
  }

}
