<?php

namespace Drupal\reservation_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class CalendarRenderController.
 */
class CalendarRenderController extends ControllerBase {
  private $current_user;
  private $entity_query;
  private $entity_manager;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxyInterface $account, QueryFactory $entity_query, EntityManagerInterface $entity_manager) {
    $this->current_user = $account;
    $this->entity_query = $entity_query;
    $this->entity_manager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity.query'),
      $container->get('entity.manager')
    );
  }

  /**
   * Render.
   *
   * @return string
   *   Return Hello string.
   */
  public function render($user, $nid) {
    /**
     * Check if current user has permission to access user content calendar.
     * if user is administrator or if he is visiting own profile he will be able to access
     * else throw new accessdenied exception
     */
    if($this->current_user->id() == $user || $this->current_user->getRoles()[1] == 'administrator') {

    } else {
      throw new AccessDeniedHttpException();
    }

    $query = $this->entity_query->get('node')
      ->condition('type', 'rezervacije')
      ->condition('field_artikl', $nid)
      ->condition('field_datum', date('Y-m-d'), '>');

    $nids = $query->execute();
    $node_storage = $this->entity_manager->getStorage('node');

    $dates = [];

    foreach ($nids as $node_id) {
      $node_loaded = $node_storage->load($node_id);
      $date = $node_loaded->get('field_datum')->value;
      $note = $node_loaded->get('field_biljeska')->value;
      $arr = [];
      $arr['date'] = $date;
      $arr['value'] = $note;
      $dates[] = $arr;
    }

    return [
      '#theme' => 'reservation_calendar',
      '#cache' => ['max-age' => 0,],
      '#attached' => [
        'library' => [
          'reservation_calendar/reservation_calendar.main_library',
        ],
        'drupalSettings' => [
          'dates' => $dates,
          'nid' => $nid,
          'content_owner' => $user,
        ],
      ],
    ];
  }

  public function getUserContent($user) {
    /**
     * Check if current user has permission to access user article tab.
     * if user is administrator or if he is visiting own profile he will have access
     * else throw new accessdenied exception
     */
    if($this->current_user->id() == $user || $this->current_user->getRoles()[1] == 'administrator') {

    } else {
      throw new AccessDeniedHttpException();
    }

    /**
     * Find all user nodes.
     */
    $query = $this->entity_query->get('node')
      ->condition('uid', $user)
      ->condition('status', 1);

    // Execute final query. $nids is array of node id's which matches the query.
    $nids = $query->execute();

    $node_storage = $this->entity_manager->getStorage('node');
    // Load nodes in array
    $nodes = [];
    foreach ($nids as $nid) {
      $node_loaded = $node_storage->load($nid);
      $node = [];
      $node['nid'] = $node_loaded->id();
      $node['title'] = $node_loaded->get('title')->value;
      $nodes[] = $node;
    }

    return [
      '#theme' => 'usercontent',
      '#nodes' => $nodes,
      '#user' => $user,
    ];
  }
}
