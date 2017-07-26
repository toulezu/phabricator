<?php

final class PhabricatorProjectListView extends AphrontView {

  private $projects;
  private $showMember;
  private $showWatching;
  private $noDataString;

  public function setProjects(array $projects) {
    $this->projects = $projects;
    return $this;
  }

  public function getProjects() {
    return $this->projects;
  }

  public function setShowWatching($watching) {
    $this->showWatching = $watching;
    return $this;
  }

  public function setShowMember($member) {
    $this->showMember = $member;
    return $this;
  }

  public function setNoDataString($text) {
    $this->noDataString = $text;
    return $this;
  }

  public function renderList() {
    $viewer = $this->getUser();
    $viewer_phid = $viewer->getPHID();
    $projects = $this->getProjects();

    $handles = $viewer->loadHandles(mpull($projects, 'getPHID'));

    $no_data = pht('No projects found.');
    if ($this->noDataString) {
      $no_data = $this->noDataString;
    }

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer)
      ->setNoDataString($no_data);

    foreach ($projects as $key => $project) {
      // 加载一个项目对应的所有maniphest对象
      $taskEdge = PhabricatorEdgeQuery::loadEdgeDatas(
            $project->getPHID(),
            '42', // 42 表示项目和任务的对应关系
            '');

      $taskslink = array();
      if (!empty($taskEdge)) {
        $count = 0;
        foreach ($taskEdge as $key => $value) {
          $taskPHID = $key;
          $task = id(new ManiphestTask())->loadOneWhere("phid = '".$key."' and subtype = 'default' and status != 'closed' ");
          if ($task !== null) {
            // 任务的链接
            $tasklink = phutil_tag(
             'a',
             array(
              'href' => "/T".$task->getID(),
              'class' => 'phui-oi-link',
              'title' => $task->getTitle(),
             ),
             $task->getTitle());
            if ($count >= 5) { // 这里最多显示5个主任务
              break;
            }
            $count++;
            $taskslink[] = $tasklink;
            $taskslink[] = ' ';
          }
        }
      }

      $id = $project->getID();

      $icon = $project->getDisplayIconIcon();
      $icon_icon = id(new PHUIIconView())
        ->setIcon($icon);

      $icon_name = $project->getDisplayIconName();

      // 这里加上任务数量和最新的任务链接
      $item = id(new PHUIObjectItemView())
        ->setHeader($project->getName())
        ->setHref("/project/view/{$id}/")
        ->setImageURI($project->getProfileImageURI())
        ->addAttribute(
          array(
            $icon_icon,
            ' ',
            $icon_name,
            ' ',
            count($taskEdge),
            ' ',
            $taskslink,
          ));

      if ($project->getStatus() == PhabricatorProjectStatus::STATUS_ARCHIVED) {
        $item->addIcon('fa-ban', pht('Archived'));
        $item->setDisabled(true);
      }

      if ($this->showMember) {
        $is_member = $project->isUserMember($viewer_phid);
        if ($is_member) {
          $item->addIcon('fa-user', pht('Member'));
        }
      }

      if ($this->showWatching) {
        $is_watcher = $project->isUserWatcher($viewer_phid);
        if ($is_watcher) {
          $item->addIcon('fa-eye', pht('Watching'));
        }
      }

      $list->addItem($item);
    }

    return $list;
  }

  public function render() {
    return $this->renderList();
  }

}
