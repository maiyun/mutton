<?php

namespace main {

	use C\ctr;
	use C\lib\Mysql;

	class main extends ctr {

		public function main() {

			echo '【' . $this->getRunTime() . '】';
			if(Mysql::connect()) {
				var_dump(Mysql::isConnected());
			} else {
				var_dump(Mysql::getError());
			}
			echo '【' . $this->getRunTime() . '】';

		}

		public function action() {

			echo '12345';

		}

	}

}

