<?php

/**
 * @file
 * Contains \Drupal\message\MessageTypeForm.
 */

namespace Drupal\message\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\MessageType;
use Drupal\message\FormElement\MessageTypeMultipleTextField;

/**
 * Form controller for node type forms.
 */
class MessageTypeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\message\Entity\MessageType
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var MessageType $type */
    $type = $this->entity;

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this message type. This text will be displayed as part of the list on the <em>Add message</em> page. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => array(
        'exists' => '\Drupal\message\Entity\MessageType::load',
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this message type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %message-add page, in which underscores will be converted into hyphens.', array(
        '%message-add' => t('Add message'),
      )),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->getDescription(),
      '#description' => t('The human-readable description of this message type.'),
    );

    $multiple = new MessageTypeMultipleTextField($this->entity, array(get_class($this), 'addMoreAjax'));
    $multiple->textField($form, $form_state);

    $data = $this->entity->getdata();

    $form['data'] = array(
      // Placeholder for other module to add their settings, that should be
      // added to the data column.
      '#tree' => TRUE,
    );

    $form['data']['token options']['clear'] = array(
      '#title' => t('Clear empty tokens'),
      '#type' => 'checkbox',
      '#description' => t('When this option is selected, empty tokens will be removed from display.'),
      '#default_value' => isset($data['token options']['clear']) ? $data['token options']['clear'] : FALSE,
    );

    $form['data']['purge'] = array(
      '#type' => 'fieldset',
      '#title' => t('Purge settings'),
    );

    $form['data']['purge']['override'] = array(
      '#title' => t('Override global settings'),
      '#type' => 'checkbox',
      '#description' => t('Override global purge settings for messages of this type.'),
      '#default_value' => !empty($data['purge']['override']),
    );

    $states = array(
      'visible' => array(
        ':input[name="data[purge][override]"]' => array('checked' => TRUE),
      ),
    );

    $form['data']['purge']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Purge messages'),
      '#description' => t('When enabled, old messages will be deleted.'),
      '#states' => $states,
      '#default_value' => !empty($data['purge']['enabled']) ? TRUE : FALSE,
    );

    $states = array(
      'visible' => array(
        ':input[name="data[purge][enabled]"]' => array('checked' => TRUE),
      ),
    );

    $form['data']['purge']['quota'] = array(
      '#type' => 'textfield',
      '#title' => t('Messages quota'),
      '#description' => t('Maximal (approximate) amount of messages of this type.'),
      '#default_value' => !empty($data['purge']['quota']) ? $data['purge']['quota'] : '',
      '#states' => $states,
    );

    $form['data']['purge']['days'] = array(
      '#type' => 'textfield',
      '#title' => t('Purge messages older than'),
      '#description' => t('Maximal message age in days, for messages of this type.'),
      '#default_value' => !empty($data['purge']['days']) ? $data['purge']['days'] : '',
      '#states' => $states,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save message type');
    $actions['delete']['#value'] = t('Delete message type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    return $form['text'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue('text');
    usort($values, 'message_order_text_weight');

    // Saving the message text values.
    foreach ($values as $key => $value) {
      $values[$key] = $value['value'];
    }

    $this->entity->set('text', $values);
    $this->entity->save();

    $params = array(
      '@type' => $form_state->getValue('label'),
    );

    drupal_set_message(t('The message type @type created successfully.', $params));
    $form_state->setRedirect('message.overview_types');
    return $this->entity;
  }

}
