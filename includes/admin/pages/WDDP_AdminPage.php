<?php

abstract class WDDP_AdminPage {

    //TODO: REFACT AND DOC


    public function __construct(){
        $this->addToMenu();
    }


    public function addToMenu(){

        if(empty($this->getParentSlug())){
            add_menu_page(
                __($this->getTitle(), WT_DOG_PENSION_TEXT_DOMAIN),
                __($this->getTitle(), WT_DOG_PENSION_TEXT_DOMAIN),
                'manage_options',
                $this->getSlug(),
                [$this, 'renderPage' ],
                $this->getIcon(),
                $this->getMenuOrder()
            );
        } else {
            add_submenu_page(
                $this->getParentSlug(),
                __($this->getTitle(), WT_DOG_PENSION_TEXT_DOMAIN),
                __($this->getTitle(), WT_DOG_PENSION_TEXT_DOMAIN),
                'manage_options',
                $this->getSlug(),
                [ $this, 'renderPage' ]
            );
        }
    }

    public abstract function renderPage();

    public abstract function getTitle();

    public abstract function getSlug();

    public abstract function getParentSlug();

    public abstract function getIcon();

    public abstract function getMenuOrder();


}