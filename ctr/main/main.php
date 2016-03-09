<?php

namespace main {

	use C\ctr;
	use C\lib\Db;
	use C\lib\Sql;

	class main extends ctr {

		public function main() {

			//$sql = new Sql();
			//$sql->db = false;
			echo $this->getRunTime();
			//echo $sql->select('*', 'user')->where(['city', ['type', '>', 'r']])->sql;
			echo $this->getRunTime();
			//*/

		}

		public function action() {

			echo '12345';

		}

	}

}

