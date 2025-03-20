<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AppTitle extends Component
{
    public $pageTitle;
    public $rank;
    public $joiningDate;
    public $activatedDate;
    public $headerIcon;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($pageTitle,$rank,$joiningDate,$activatedDate,$headerIcon)
    {
        $this->pageTitle = $pageTitle;
        $this->rank = $rank;
        $this->joiningDate = $joiningDate;
        $this->activatedDate = $activatedDate;
        $this->headerIcon = $headerIcon;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.app-title');
    }

}
