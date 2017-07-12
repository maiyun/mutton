<?php

namespace main {

	use C\ctr;

    class main extends ctr {

		public function main() {

			echo 'Hello world! Welcome to use Chameleon ' . VER;

            echo '<br><br>URI: ' . URI . '.';
			echo '<br>HTTPS: ' . ($this->isHttps() ? 'true' : 'false') . '.';
            echo '<br>PHP Verison: ' . PHP_VERSION;

            echo '<br><br><b>ROUTE(etc/set.php):</b>';
            echo '<br><br><a href="'.HTTP_PATH.'article/123">View "article/123"</a>';
            echo '<br><a href="'.HTTP_PATH.'article/456">View "article/456"</a>';

            echo '<br><br><b>AUTO ROUTE:</b>';
            echo '<br><br><a href="'.HTTP_PATH.'main/auto">View "main/auto"</a>';

            echo '<br><br>Processed in '.$this->getRunTime().' second(s)';

		}

		public function article() {

			echo 'Article ID: ' . $this->param[0];

		}

		public function auto() {

            $this->loadView('main/auto');

        }

	}

}

