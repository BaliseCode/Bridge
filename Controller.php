<?php
// SetUp Controller
namespace Balise\Bridge;
use Windwalker\Renderer\BladeRenderer;

class Controller {
    protected function view($view, $data=array()) {
        $ns = substr(get_called_class(), 0, strrpos(get_called_class(), "\\"));
        if (defined ($ns. '\ViewDir') && defined ($ns. '\ViewCache')) {
           $renderer = new BladeRenderer(constant($ns. '\ViewDir'), array('cache_path' => constant($ns. '\ViewCache')));
           return $renderer->render($view, $data);
        } else {
            throw new \Exception("Please define ".$ns."\ViewDir and  ".$ns."\ViewCache.\n");
        }
           
        
                
    }
}
