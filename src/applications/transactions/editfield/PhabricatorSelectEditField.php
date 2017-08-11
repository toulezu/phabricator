<?php

final class PhabricatorSelectEditField
  extends PhabricatorEditField {

  private $options;
  private $optionAliases = array();

  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  public function getOptions() {
    if ($this->options === null) {
      throw new PhutilInvalidStateException('setOptions');
    }
    // 编辑任务对象,任务对象必须存在,根据不同类型的任务对象来设置不同的 status 选项
    if ($this->getObject() !== null &&
        $this->getObject()->getPHID() !== null &&
        $this->getObject() instanceof ManiphestTask &&
        $this->getKey() === 'status') {
      if ($this->getObject()->getEditEngineSubtype() === 'default') {
        return array(
         'open' => 'Open',
         'test' => 'Test',
         'closed' => 'Closed',
        );
      }
      if ($this->getObject()->getEditEngineSubtype() === 'test') {
        return array(
         'open' => 'Open',
         'closed' => 'Closed',
        );
      }
      if ($this->getObject()->getEditEngineSubtype() === 'dev') {
        return array(
         'open' => 'Open',
         'closed' => 'Closed',
        );
      }
      if ($this->getObject()->getEditEngineSubtype() === 'bug') {
        return array(
         'open' => 'Open',
         'resolved' => 'Resolved',
         'wontfix' => 'Wontfix',
         'invalid' => 'Invalid',
         'closed' => 'Closed',
        );
      }
    }

    return $this->options;
  }

  public function setOptionAliases(array $option_aliases) {
    $this->optionAliases = $option_aliases;
    return $this;
  }

  public function getOptionAliases() {
    return $this->optionAliases;
  }

  protected function getValueForControl() {
    $value = parent::getValueForControl();

    $options = $this->getOptions();
    if (!isset($options[$value])) {
      $aliases = $this->getOptionAliases();
      if (isset($aliases[$value])) {
        $value = $aliases[$value];
      }
    }

    return $value;
  }

  protected function newControl() {
    return id(new AphrontFormSelectControl())
      ->setOptions($this->getOptions());
  }

  protected function newHTTPParameterType() {
    return new AphrontSelectHTTPParameterType();
  }

  protected function newCommentAction() {
    return id(new PhabricatorEditEngineSelectCommentAction())
      ->setOptions($this->getOptions());
  }

  protected function newConduitParameterType() {
    return new ConduitStringParameterType();
  }

}
