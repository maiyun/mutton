<?php

namespace main {

	use C\ctr;
	use C\lib\Db;
	use C\lib\Sql;

	class main extends ctr {

		public function main() {

			Db::connect();
			//*/

		}

		public function action() {

			echo '12345';

		}

	}

}

