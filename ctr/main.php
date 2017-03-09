<?php

namespace main {

	use C\ctr;

    class main extends ctr {

		public function main() {

			echo 'Hello world! Welcome to use Chameleon ' . VER;

            echo '<br><br>URI: ' . URI . '.';
			echo '<br>HTTPS: ' . ($this->isHttps() ? 'true' : 'false') . '.';

            echo '<br><br><a href="'.HTTP_PATH.'article/123">View "article/123"</a>';

            echo '<br><br>Processed in '.$this->getRunTime().' second(s)';

		}

		public function article() {

			echo 'Article ID: ' . $this->param[0];

		}

	}

}

