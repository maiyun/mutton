<?php

namespace main {

	use C\ctr;

	class main extends ctr {

		public function main() {

			echo 'Hello world! Welcome to use Chameleon ' . VER;

			echo '<br><br>https: ' . ($this->isHttps() ? 'true' : 'false') . '.';

		}

		public function action() {

			echo '12345';

		}

	}

}

