<?php
namespace Golden13\Scb;

// storages
interface StorageInterface {
    public function set(ScbStatus $status);
    public function get();

    public function linkItem(ScbItem &$item);

}


