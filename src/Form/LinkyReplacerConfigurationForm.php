<?php

declare(strict_types = 1);

namespace Drupal\linkyreplacer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General configuration settings.
 */
class LinkyReplacerConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['linkyreplacer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linky_replacer_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['internal_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Internal sites'),
      '#description' => $this->t('Enter one URL (without "http://") per line to tag as an internal site. Use an asterisk as a wildcard to allow all sub domains.'),
      '#default_value' => $this->config('linkyreplacer.settings')->get('internal_patterns'),
      '#config' => [
        'key' => 'linkyreplacer.settings:domains',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Ensure patterns do not match each other, this is to prevent
    // redundant/excessive regex computation.
    $domains = $form_state->getValue('internal_patterns');
    $domains = array_filter(preg_split('/\R/', $domains));
    $errors = static::checkRedundantDomains($domains);
    foreach ($errors as $error) {
      $form_state->setError($form['internal_patterns'], $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $domains = $form_state->getValue('internal_patterns');
    $this->config('linkyreplacer.settings')
      ->set('internal_patterns', $domains)
      ->save();
  }

  /**
   * Check if any domains match each other.
   *
   * @param string[] $domains
   *   An array of domain rules.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of errors, if any.
   */
  protected function checkRedundantDomains(array $domains): array {
    $regexDomains = $domains;
    foreach ($regexDomains as $k => $domain) {
      $domain = preg_quote($domain);
      // Replace the escaped asterisk character with regex compatible wildcard.
      $domain = str_replace('\*', '.*', $domain);
      $regexDomains[$k] = '/^' . $domain . '$/';
    }

    $errors = [];
    foreach ($domains as $k => $domain) {
      $cleanDomain = str_replace('*', '', $domain);
      $otherDomains = $regexDomains;
      unset($otherDomains[$k]);
      foreach ($otherDomains as $j => $regex) {
        if (preg_match($regex, $cleanDomain)) {
          $errors[] = \t('Cannot add @domain to domains as it matches against another broader rule: @other', [
            '@domain' => $domain,
            '@other' => $domains[$j],
          ]);
        }
      }
    }

    return $errors;
  }

}
