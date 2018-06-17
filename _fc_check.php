<!DOCTYPE HTML>
<html>
	<head>
		<script src="//cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.min.js"></script>
		<script src="//cdn.jsdelivr.net/npm/jquery@3.3.1/dist/jquery.min.js"></script>
		<script>
			$(document).ready(function() {
			    // --- 按下状态 ---
				var KEYDOWN = false;
				// --- 是否允许输入 ---
				var RUNNING = false;
				// --- 创建 VUE ---
				var APP = new Vue({
					data: {
						showGb: true,
						input: [],
						list: []
					},
					el: "#app"
				});
				// --- 光标的显示与隐藏闪烁 ---
				var TIMER = setInterval(function() {
					APP.showGb = APP.showGb ? false : true;
				}, 800);
				// --- 输入 ---
				$(document).on('keypress', function(e) {
					if (!RUNNING) {
						if (e.which === 13) {
							var action = APP.input.join('');
							APP.list.push('> ' + action);
							RUNNING = true;
							APP.input = [];
						} else {
							APP.input.push(e.key);
						}
					}
					e.preventDefault();
				}).on('keydown', function(e) {
                    if (e.which === 8) {
                        APP.input.splice(-1, 1);
                        e.preventDefault();
                    }
					if (!KEYDOWN) {
						KEYDOWN = true;
						clearInterval(TIMER);
						APP.showGb = true;
					}
				}).on('keyup', function() {
					if (KEYDOWN) {
						APP.showGb = false;
						TIMER = setInterval(function() {
							APP.showGb = APP.showGb ? false : true;
						}, 800);
						KEYDOWN = false;
					}
				});
			});
		</script>
		<title>Framework check tool</title>
		<style>
			body,html{background-color: #000; color: #FFF; font-size: 18px; font-family:Consolas, Monaco, monospace; cursor: default; margin:0;}
			#app{padding: 10px 10px 5px 10px;}
			.line{margin-bottom: 5px;}
		</style>
	</head>
	<body>
		<?php
$dir = "img/";

if (is_dir($dir)){
  if ($dh = opendir($dir)){
    while (($file = readdir($dh)) !== false){
      echo "filename:" . $file . "<br>";
    }
    closedir($dh);
  }
}
?>
		<div id="app">
			<div v-for="line of list" class="line">{{line}}</div>
        	<div>&gt;&nbsp;{{input.join('')}}<span v-show="showGb">_</span></div>
        </div>
	</body>
</html>