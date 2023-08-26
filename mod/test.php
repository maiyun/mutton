<?php
/*
CREATE TABLE `m_test` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `token` CHAR(16) NOT NULL COLLATE 'ascii_bin',
  `point` POINT NOT NULL,
  `polygon` POLYGON NULL DEFAULT NULL,
  `time_add` BIGINT NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `token` (`token`) USING BTREE,
	INDEX `time_add` (`time_add`) USING BTREE
) ENGINE=InnoDB COLLATE=utf8mb4_general_ci;
*/
declare(strict_types = 1);

namespace mod;

class Test extends Mod {

    protected static $_table = 'test';

    protected static $_primary = 'id';

    protected static $_key = 'token';

    public $id, $token, $point, $polygon, $time_add;

    protected function _keyGenerator(): string {
        return 'test_' . rand(0, 5);
    }

}

