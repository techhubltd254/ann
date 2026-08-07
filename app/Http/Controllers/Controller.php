<?php

namespace App\Http\Controllers;

abstract class Controller extends \Illuminate\Routing\Controller
{
    // Extends the framework base so controllers can use $this->middleware(),
    // authorize(), validate() helpers etc. (empty app skeleton breaks them).
}
