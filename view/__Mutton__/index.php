<!DOCTYPE HTML>
<html>
<head>
    <title>Mutton Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.min.js"></script>
    <script src="<?php echo HTTP_STC ?>__Mutton__/index.js?4"></script>
    <link rel="stylesheet" href="<?php echo HTTP_STC ?>__Mutton__/index.css?3">
</head>
<body>
<div id="vue">
	<div class="title">Mutton Portal</div>
    <div class="tab">
        <div class="tab__top">
            <div class="tab__top__item" :class="{'selected': tab == 0}" @click="tab = 0"><div class="tab__top__item__in">Framework</div></div>
            <div class="tab__top__item" :class="{'selected': tab == 1}" @click="tab = 1"><div class="tab__top__item__in">Virtual Host</div></div>
        </div>
        <div class="tab__panel">
            <!-- Freamwork -->
            <div class="tab__panel__item" v-if="tab == 0">
                0
                <div class="button"><div class="button__in">Button</div></div>
                <div class="textbox"><input class="textbox__in" value="aaa"></div>
            </div>
            <!-- Virtual Host -->
            <div class="tab__panel__item" v-else="tab == 1">
                1
            </div>
        </div>
    </div>
</div>
</body>
</html>