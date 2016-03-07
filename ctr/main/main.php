<?php

namespace main {

	use C\ctr;
	use C\lib\Mysql;
	use C\lib\Sql;

	class main extends ctr {

		public function main() {

			$sqlObj = new Sql();
			$sqlObj->lib = NULL;
			echo '【' . $this->getRunTime() . '】';
				echo $sqlObj->escape('sd\'sd"sadf');
			echo '【' . $this->getRunTime() . '】';
			//*/

		}

		public function action() {

			echo '12345';

		}

	}

}

