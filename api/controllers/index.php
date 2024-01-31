<?php

class Index extends Controller
{
  function __construct()
  {
    parent::__construct();
    (new Session)->init();
    Header('Location: ' . URL . 'admin');
  }

  function index()
  {
    require 'models/index_model.php';
    $this->model = new Index_Model();

    $this->view->title = 'The best delivery platform';
    $this->view->sitemap = false;
    $this->view->data = [
      $this->model->product(),
      $this->model->service(),
      $this->model->category(),
      $this->model->slider(),
      $this->model->blog()
    ];
    $this->view->render('header');
    $this->view->render('pages/index');
    $this->view->render('footer');
  }
}