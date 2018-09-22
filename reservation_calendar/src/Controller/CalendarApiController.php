<?php

namespace Drupal\reservation_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Masterminds\HTML5\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class CalendarApiController.
 */
class CalendarApiController extends ControllerBase {

  private $current_user;
  private $entity_query;
  private $entity_manager;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxyInterface $account,QueryFactory $entity_query, EntityManagerInterface $entity_manager) {
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
   * Add.
   *
   * @return string
   *   Return Hello string.
   */
  public function add(Request $request) {
    $params = [];
    $content = $request->getContent();
    if(!empty($content)) {
      $params = json_decode($content, TRUE);
    }

    $res = [];
    $res['status'] = 1;
    $res['msg'] = '';

    if($this->current_user->id() == $params['content_owner'] || $this->current_user->getRoles()[1] == 'administrator') {

    } else {
      $res['status'] = 0;
      $res['msg'] = 'Current user has no privileges to complete this operation.';
      return new JsonResponse($res);
    }

    $node_storage = $this->entity_manager->getStorage('node');

    try {
      $node = $node_storage->load($params['nid']);
      $data = [
          'type' => 'rezervacije',
          'title' => 'Nova rezervacija: ' . $node->get('title')->value,
          'field_artikl' => $params['nid'],
          'field_biljeska' => $params['note'],
          'field_datum' => $params['date'],
          'uid' => $this->current_user->id(),
        ];
      $new_reservation_node = $node_storage
        ->create($data);
      $new_reservation_node->save();

    } catch (Exception $e) {
      $res['status'] = 0;
      $res['msg'] = $e->getMessage();
    }

    return new JsonResponse($res);
  }
  /**
   * Delete.
   *
   * @return string
   *   Return Hello string.
   */
  public function delete(Request $request) {
    $params = [];
    $content = $request->getContent();
    if(!empty($content)) {
      $params = json_decode($content, TRUE);
    }

    $res = [];
    $res['status'] = 1;
    $res['msg'] = '';

    if($this->current_user->id() == $params['content_owner'] || $this->current_user->getRoles()[1] == 'administrator') {

    } else {
      $res['status'] = 0;
      $res['msg'] = 'Current user has no privileges to complete this operation.';
      return new JsonResponse($res);
    }
    $query = $this->entity_query->get('node')
      ->condition('type', 'rezervacije')
      ->condition('field_artikl', $params['nid'])
      ->condition('field_datum', $params['date']);
    $nids = $query->execute();

    $node_storage = $this->entity_manager->getStorage('node');

    try {
      foreach ($nids as $nid) {
        $node = $node_storage->load($nid);
        $node->delete();
        break;
      }
    } catch (Exception $e) {
      $res['status'] = 0;
      $res['msg'] = $e->getMessage();
    }

    return new JsonResponse($res);
  }
  /**
   * Update.
   *
   * @return string
   *   Return Hello string.
   */
  public function update(Request $request) {
    $params = [];
    $content = $request->getContent();
    if(!empty($content)) {
      $params = json_decode($content, TRUE);
    }

    $res = [];
    $res['status'] = 1;
    $res['msg'] = '';

    if($this->current_user->id() == $params['content_owner'] || $this->current_user->getRoles()[1] == 'administrator') {

    } else {
      $res['status'] = 0;
      $res['msg'] = 'Current user has no privileges to complete this operation.';
      return new JsonResponse($res);
    }

    $query = $this->entity_query->get('node')
      ->condition('type', 'rezervacije')
      ->condition('field_artikl', $params['nid'])
      ->condition('field_datum', $params['date']);
    $nids = $query->execute();

    $node_storage = $this->entity_manager->getStorage('node');

    try {
      foreach ($nids as $nid) {
        $node = $node_storage->load($nid);
        $node->field_biljeska->value = $params['note'];
        $node->save();
        break;
      }
    } catch (Exception $e) {
      $res['status'] = 0;
      $res['msg'] = $e->getMessage();
    }

    return new JsonResponse($res);
  }

}
