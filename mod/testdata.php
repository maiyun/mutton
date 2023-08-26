<?php
/*
CREATE TABLE `m_test_data_0` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `test_id` bigint NOT NULL,
    `content` varchar(128) COLLATE ascii_bin NOT NULL,
    `time_add` bigint NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;
*/
declare(strict_types = 1);

namespace mod;

class TestData extends Mod {

    protected static $_table = 'test_data';
    protected static $_primary = 'id';

    public $id, $test_id, $content, $time_add;

}

