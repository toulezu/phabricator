<?php

final class ShoppingCarController extends PhabricatorController {

  private $view;
    
  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $this->view = $request->getURIData('view');
      
    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI('/shoppingcar/'));
    $nav->addLabel(pht('基本信息'));
    $nav->addFilter('add', pht('添加基本信息'));
    $nav->addFilter('list', pht('显示列表'));
    $nav->addLabel(pht('Burnup'));
    $nav->addFilter('burn', pht('Burnup Rate'));
    
    $this->view = $nav->selectFilter($this->view, 'add');
    
    switch ($this->view) {
        case 'burn':
            $core = $this->renderBurn();
            break;
        case 'add':
            $core = $this->renderAdd();
            break;
        case 'list':
            $core = $this->renderList();
            break;
        default:
            return new Aphront404Response();
    }
    $nav->appendChild($core);
    
    $title = pht('Shopping Car');
    
    $crumbs = $this->buildApplicationCrumbs()
    ->addTextCrumb(pht('Shopping Car Demo'));
    
    return $this->newPage()
    ->setTitle($title)
    ->setCrumbs($crumbs)
    ->setNavigation($nav);
  }
  
  public function renderAdd() {
      $request = $this->getRequest();
      $viewer = $request->getUser();
      
      $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->setAction($request->getPath());
      
      $form->appendControl(id(new AphrontFormTextControl())
                ->setName('name')
                ->setLabel(pht('name')))
           ->appendControl(id(new AphrontFormTextControl())
                  ->setName('type')
                  ->setLabel(pht('type')))
           ->appendChild(id(new AphrontFormTextControl())
                  ->setLabel(pht('price'))
                  ->setName('price'))
           ->appendChild(
                  id(new AphrontFormTextControl())
                  ->setLabel(pht('status'))
                  ->setName('status'));
      
      $save_button = id(new PHUIButtonView())
       ->setTag('a')
       ->setHref('/shoppingcar/add/')
       ->setText(pht('添加'))
       ->setIcon('fa-floppy-o');
           
      $submit = id(new AphrontFormSubmitControl())
      ->addButton($save_button);
      
      $form->appendChild($submit);
      
      $table = new PHUITabView();
      $table->appendChild($form);
      
      $panel = new PHUIObjectBoxView();
      $panel->setHeaderText("添加基本信息");
      $panel->setTable($table);
      
      return array($panel);
  }
  
  public function renderList() {
      $request = $this->getRequest();
      $viewer = $request->getUser();
      
      $panel = new PHUIObjectBoxView();
      $panel->setHeaderText("this list");
      $panel->setTable(new PHUITabView());
      
      return array($panel);
  }
  
  public function renderBurn() {
      $request = $this->getRequest();
      $viewer = $request->getUser();
      
      $panel = new PHUIObjectBoxView();
      $panel->setHeaderText("this burn");
      $panel->setTable(new PHUITabView());
      
      $chart = phutil_tag(
          'div',
          array(
              'style' => 'border: 1px solid #BFCFDA; '.
              'background-color: #fff; '.
              'margin: 8px 16px; '.
              'height: 400px; ',
          ),
          '');
          
      $box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Burnup Rate'))
      ->appendChild($chart);
      
      return array($box, $panel);
  }
  
  public function buildSideNavView() {
      $viewer = $this->getViewer();
      
      $nav = new AphrontSideNavFilterView();
      $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));
      
      id(new ShoppingCarSearchEngine())
      ->setViewer($viewer)
      ->addNavigationItems($nav->getMenu());
      
      if ($viewer->isLoggedIn()) {
          // For now, don't give logged-out users access to reports.
          $nav->addLabel(pht('Reports'));
          $nav->addFilter('report', pht('Reports'));
      }
      
      $nav->selectFilter(null);
      
      return $nav;
  }
  
  private function test() {
      $request = $this->getRequest();
      $user = $request->getUser();
      
      $nav = $this->buildSideNavView();
      
      //$body = array('hello');
      $body = phutil_tag_div('div', 'test shopping car content');
      
      $title = pht('test');
      
      $header = id(new PHUIHeaderView())
      ->setHeader($title)
      ->setProfileHeader(true);
      
      $box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->addClass('application-search-results');
      
      $crumbs = $this
      ->buildApplicationCrumbs()
      ->setBorder(true);
      
      require_celerity_resource('application-search-view-css');
      
      return $this->newPage()
      ->setApplicationMenu($this->buildApplicationMenu())
      ->setTitle(pht('Query: %s', $title))
      ->setCrumbs($crumbs)
      ->setNavigation($nav)
      ->addClass('application-search-view')
      ->appendChild($body);
  }


}
