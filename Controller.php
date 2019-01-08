<?php
// SetUp Controller
namespace Balise\Bridge;
use Windwalker\Renderer\BladeRenderer;

class Controller {
    protected function view() {
            echo substr(get_called_class(), 0, strrpos(get_called_class(), "\\"));
            $renderer = new BladeRenderer();
                
    }
}
