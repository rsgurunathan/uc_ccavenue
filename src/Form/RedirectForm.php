<?php

namespace Drupal\uc_ccavenue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class RedirectForm extends FormBase {

  /**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_ec_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $this->order = $order;
    \Drupal::service('plugin.manager.uc_payment.method')
      ->createFromOrder($this->order)
      ->submitExpressReviewForm($form, $form_state, $this->order);

    //return $build;


    //return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
 
  }
}
