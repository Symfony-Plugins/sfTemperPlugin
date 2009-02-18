<?php
class sfTemperPluginActions extends sfActions
{
  // Symfony 1.0 Stuff
  public function executeLoad()
  {
    $this->variables = $this->getUser()->getAttributeHolder()->getAll('symfony/flash');
    return sfView::SUCCESS;
  }
}