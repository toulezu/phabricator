<?php

final class ManiphestTaskGraph
  extends PhabricatorObjectGraph {

  private $seedMaps = array();

  protected function getEdgeTypes() {
    return array(
      ManiphestTaskDependedOnByTaskEdgeType::EDGECONST,
      ManiphestTaskDependsOnTaskEdgeType::EDGECONST,
    );
  }

  protected function getParentEdgeType() {
    return ManiphestTaskDependsOnTaskEdgeType::EDGECONST;
  }

  protected function newQuery() {
    return new ManiphestTaskQuery();
  }

  protected function isClosed($object) {
    return $object->isClosed();
  }

  protected function newTableRow($phid, $object, $trace) {
    $viewer = $this->getViewer();

    if ($object) {
      $status = $object->getStatus();
      $priority = $object->getPriority();
      $status_icon = ManiphestTaskStatus::getStatusIcon($status);
      $status_name = ManiphestTaskStatus::getTaskStatusName($status);

      $priority_color = ManiphestTaskPriority::getTaskPriorityColor($priority);
      if ($object->isClosed()) {
        $priority_color = 'grey';
      }

      $status = array(
        id(new PHUIIconView())->setIcon($status_icon, $priority_color),
        ' ',
        $status_name,
      );

      $owner_phid = $object->getOwnerPHID();
      if ($owner_phid) {
        $assigned = $viewer->renderHandle($owner_phid);
      } else {
        $assigned = phutil_tag('em', array(), pht('None'));
      }

      $task_flag= '';
      if ($object->getEditEngineSubtype() === 'default') {
        $task_flag= '[MAIN] ';
      } else if ($object->getEditEngineSubtype() === 'dev') {
        $task_flag= '[DEV] ';
      } else if ($object->getEditEngineSubtype() === 'test') {
        $task_flag= '[TEST] ';
      } else if ($object->getEditEngineSubtype() === 'bug') {
        $task_flag= '[BUG] ';
      }

      $full_title = $task_flag.$object->getTitle();

      $link = phutil_tag(
        'a',
        array(
          'href' => $object->getURI(),
          'title' => $full_title,
        ),
        $full_title);

      // 当主任务变成Test状态的时候增加提测链接
      $submit_test_div = '';
      if ($object instanceof ManiphestTask
        && $object->getStatus() === 'test'
        && $object->getEditEngineSubtype() === 'default') {

        $submit_test_link = phutil_tag(
         'a',
         array(
          'href' => 'http://finance.tools.qa.nt.ctripcorp.com/BigScm/com.ctrip.scm.web.view.release.PhaRnApply.d?taskId=T'.$object->getID(),
          'title' => '提测',
          'style' => 'padding-left: 3px; font-weight: bold; color: #8E44AD;',
          'target' => '_blank',
         ),
         '提测');

        $submit_test_div = phutil_tag('div',
         array(
          'class' => 'phui-font-fa fa-external-link',
          'style' => 'margin-left: 100px; color: #8E44AD;',
          'aria-hidden' => 'true',
         ),
        $submit_test_link);
      }

      $link = array(
        phutil_tag(
          'span',
          array(
            'class' => 'object-name',
          ),
          $object->getMonogram()),
        ' ',
        $link,
        ' ',
        $submit_test_div,
      );
    } else {
      $status = null;
      $assigned = null;
      $link = $viewer->renderHandle($phid);
    }

    if ($this->isParentTask($phid)) {
      $marker = 'fa-chevron-circle-up bluegrey';
      $marker_tip = pht('Direct Parent');
    } else if ($this->isChildTask($phid)) {
      $marker = 'fa-chevron-circle-down bluegrey';
      $marker_tip = pht('Direct Subtask');
    } else {
      $marker = null;
    }

    if ($marker) {
      $marker = id(new PHUIIconView())
        ->setIcon($marker)
        ->addSigil('has-tooltip')
        ->setMetadata(
          array(
            'tip' => $marker_tip,
            'align' => 'E',
          ));
    }

    return array(
      $marker,
      $trace,
      $status,
      $assigned,
      $link,
    );
  }

  protected function newTable(AphrontTableView $table) {
    return $table
      ->setHeaders(
        array(
          null,
          null,
          pht('Status'),
          pht('Assigned'),
          pht('Task'),
        ))
      ->setColumnClasses(
        array(
          'nudgeright',
          'threads',
          'graph-status',
          null,
          'wide pri object-link',
        ))
      ->setColumnVisibility(
        array(
          true,
          !$this->getRenderOnlyAdjacentNodes(),
        ));
  }

  private function isParentTask($task_phid) {
    $map = $this->getSeedMap(ManiphestTaskDependedOnByTaskEdgeType::EDGECONST);
    return isset($map[$task_phid]);
  }

  private function isChildTask($task_phid) {
    $map = $this->getSeedMap(ManiphestTaskDependsOnTaskEdgeType::EDGECONST);
    return isset($map[$task_phid]);
  }

  private function getSeedMap($type) {
    if (!isset($this->seedMaps[$type])) {
      $maps = $this->getEdges($type);
      $phids = idx($maps, $this->getSeedPHID(), array());
      $phids = array_fuse($phids);
      $this->seedMaps[$type] = $phids;
    }

    return $this->seedMaps[$type];
  }

  protected function newEllipsisRow() {
    return array(
      null,
      null,
      null,
      null,
      pht("\xC2\xB7 \xC2\xB7 \xC2\xB7"),
    );
  }


}
