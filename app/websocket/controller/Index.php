<?php

namespace app\websocket\controller;

use app\BaseController;

class Index extends BaseController
{
    public function index()
    {
        return $this->fetch();
    }
}
