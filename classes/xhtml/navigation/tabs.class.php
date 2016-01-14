<?php
require_once("xhtml/placeholder.class.php");

/**
 * An HTML tabbed navigation bar
 */
class Tabs extends Placeholder
{
    private $tabs;
    private $tab_class;
    
    public function __construct(array $tabs, $tab_class='') {
        $this->tabs = $tabs;
        if ($tab_class) {
            $this->tab_class = ' ' . $tab_class;
        }
    }
    
    protected function OnPreRender() {
        
        foreach ($this->tabs as $name => $url) {
            
            if ($url) {
               $this->AddControl('<div class="tab-option tab-inactive' . Html::Encode($this->tab_class) . '"><p><a href="' . Html::Encode($url) . '">' . Html::Encode($name) . '</a></p></div>');
            } else {
              $this->AddControl('<div class="tab-option tab-active' . Html::Encode($this->tab_class) . '"><h2>' . Html::Encode($name) . '</h2></div>');
            }   
            
            
        }
    } 
}
?>