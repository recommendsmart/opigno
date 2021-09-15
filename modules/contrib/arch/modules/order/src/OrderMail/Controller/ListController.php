<?php

namespace Drupal\arch_order\OrderMail\Controller;

use Drupal\arch_order\OrderMail\OrderMailInterface;
use Drupal\arch_order\OrderMail\OrderMailManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for order routes.
 */
class ListController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Mail manager service.
   *
   * @var \Drupal\arch_order\OrderMail\OrderMailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs an MailController object.
   *
   * @param \Drupal\arch_order\OrderMail\OrderMailManagerInterface $mail_manager
   *   Mail manager service.
   */
  public function __construct(
    OrderMailManagerInterface $mail_manager
  ) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('arch_order_mail')
    );
  }

  /**
   * List view.
   *
   * @return array
   *   Page render array.
   */
  public function main() {
    $content = [
      'table' => [],
    ];

    $content['table'] = [
      '#type' => 'table',
      '#header' => $this->buildTableHeader(),
      '#empty' => $this->t(
        'There are no @label yet.',
        ['@label' => 'mails'],
        ['context' => 'arch_order_mail']
      ),
    ];

    foreach ($this->load() as $item) {
      $content['table'][] = $this->buildTableRow($item) + [
        '#attributes' => [
          'style' => (!$item->isEnabled() ? 'background:#efefef;font-style:italic' : ''),
        ],
      ];
    }

    return $content;
  }

  /**
   * Build table header.
   *
   * @return array
   *   Render array.
   */
  private function buildTableHeader() {
    $header = [
      'label' => $this->t('Mail name', [], ['context' => 'arch_order_mail']),
      'status' => $this->t('Status', [], ['context' => 'arch_order_mail']),
      'sendto' => $this->t('Send to', [], ['context' => 'arch_order_mail']),
      'operations' => $this->t('Operations'),
    ];

    return $header;
  }

  /**
   * Build table row.
   *
   * @param \Drupal\arch_order\OrderMail\OrderMailInterface $mail
   *   Mail plugin.
   *
   * @return array
   *   Render array.
   */
  private function buildTableRow(OrderMailInterface $mail) {
    $row = [];
    $row['label'] = [
      '#type' => 'inline_template',
      '#template' => '<label>{{ label }}</label><div>&nbsp;&nbsp;<i style="font-size:11px">{{ description }}</i></div>',
      '#context' => [
        'label' => $mail->getPluginDefinition()['label'],
        'description' => $mail->getPluginDefinition()['description'],
      ],
    ];

    $row['status'] = [
      '#markup' => $mail->isEnabled() ? $this->t('Enabled') : '<b>' . $this->t('Disabled')->render() . '</b>',
    ];

    $sendto = $mail->getPluginDefinition()['sendTo'];
    $row['sendto'] = [
      '#markup' => ($sendto == 'method' ? 'Individual' : ucfirst($sendto)),
    ];

    $row['operations'] = [
      '#type' => 'operations',
      '#links' => $this->getOperations($mail),
    ];

    return $row;
  }

  /**
   * Builds a renderable list of operation links for the mail.
   *
   * @param \Drupal\arch_order\OrderMail\OrderMailInterface $mail
   *   The mail plugin on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  private function getOperations(OrderMailInterface $mail) {
    $operations = [];

    $view_url = Url::fromRoute(
      'arch_order_mail.view',
      ['plugin_id' => $mail->getPluginId()]
    );

    $operations['view'] = [
      'title' => $this->t('View'),
      'url' => $view_url,
    ];

    return $operations;
  }

  /**
   * List of mails.
   *
   * @return \Drupal\arch_order\OrderMail\OrderMailInterface[]
   *   Mail plugin list.
   */
  protected function load() {
    /** @var \Drupal\arch_order\OrderMail\OrderMailInterface[] $list */
    $list = $this->mailManager->getAll();
    ksort($list);
    return $list;
  }

}
