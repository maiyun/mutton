<!DOCTYPE HTML>
<html>
<head>
    <title>Mutton Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
    <link rel="stylesheet" href="<?php echo HTTP_STC ?>__Mutton__/index.css?<?php echo VER ?>">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>
    <script>
        var tab = <?php echo $hasConfig ? '0' : '3' ?>;
        var HTTP_BASE = '<?php echo HTTP_BASE ?>';
    </script>
    <script src="<?php echo HTTP_STC ?>__Mutton__/index.js?<?php echo VER ?>"></script>
</head>
<body>
<div id="vue">
    <div class="window">
        <div class="window__in">
            <div class="window__title">Mutton Portal</div>
            <div class="window__panel">
                <div class="title">Mutton Portal</div>
                <div class="tab">
                    <div class="tab__top">
                        <div class="tab__top__item" :class="{'selected': tab == 0}" @click="tab = 0"><div class="tab__top__item__in"><?php echo l('Password.title') ?></div></div>
                        <div class="tab__top__item" :class="{'selected': tab == 1}" @click="tab = 1"><div class="tab__top__item__in"><?php echo l('Check') ?></div></div>
                        <div class="tab__top__item" :class="{'selected': tab == 2}" @click="tab = 2"><div class="tab__top__item__in"><?php echo l('System') ?></div></div>
                        <div class="tab__top__item" :class="{'selected': tab == 3}" @click="tab = 3"><div class="tab__top__item__in"><?php echo l('Config') ?></div></div>
                    </div>
                    <div class="tab__panel">
                        <!-- Password -->
                        <div class="tab__panel__item" v-if="tab == 0">
                            <div style="padding-bottom: 10px;"><?php echo l('Password.Please enter your password:') ?></div>
                            <div class="textbox"><input class="textbox__in" type="password" v-model="password"></div>
                            <div style="padding-top: 10px;"><?php echo l('When the input is complete, you can use other features.') ?></div>
                            <mu-line></mu-line>
                            <div style="text-align: center;">
                                <mu-button @click.native="window.location.href='?l=en'">English</mu-button>
                                <mu-button @click.native="window.location.href='?l=zh-CN'" style="margin-left: 10px;">简体中文</mu-button>
                                <mu-button @click.native="window.location.href='?l=zh-TW'" style="margin-left: 10px;">繁體中文</mu-button>
                            </div>
                        </div>
                        <!-- Check -->
                        <div class="tab__panel__item" v-else-if="tab == 1">
                            <div style="padding-bottom: 10px;"><?php echo l('Please click "Refresh" and select below:') ?></div>
                            <mu-list :list="mlist" v-model="mindex"></mu-list>
                            <div style="text-align: center; margin-top: 10px;">
                                <mu-button @click.native="refresh()"><?php echo l('Refresh') ?></mu-button>
                                <mu-button @click.native="check()" style="margin-left: 10px;"><?php echo l('Check') ?></mu-button>
                                <mu-button @click.native="check(1)" style="margin-left: 10px;"><?php echo l('Online') ?></mu-button>
                            </div>
                            <div style="margin-top: 10px;"><?php echo l('Mismatch file list:') ?></div>
                            <mu-list :list="list"></mu-list>
                        </div>
                        <!-- System -->
                        <div class="tab__panel__item" v-else-if="tab == 2">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div>
                                    <?php echo l('Current version:') ?> <?php echo VER ?><br>
                                    <?php echo l('Latest versions:') ?> {{latestVer}}<br>
                                </div>
                                <mu-button @click.native="getLatestVer()" style="margin-top: 10px;"><?php echo l('Get latest versions') ?></mu-button>
                            </div>
                            <mu-line></mu-line>
                            <div style="display: flex; flex-direction: column;">
                                <?php echo l('Automatic upgrade (used only on local testing).') ?><br>
                                <div style="display: flex; margin-top: 10px;">
                                    <mu-list :list="mlist" v-model="updateIndex" style="flex: 1;"></mu-list>
                                    <mu-list :list="updateList" style="margin-left: 10px; flex: 2;"></mu-list>
                                </div>
                                <div style="margin-top: 10px; text-align: center;">
                                    <mu-button @click.native="refresh()">Refresh</mu-button>
                                    <mu-button @click.native="update()" style="margin-left: 10px;">{{updateing ? 'Running...' : 'Start'}}</mu-button>
                                </div>
                            </div>
                            <mu-line></mu-line>
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div>Build a ".mblob" file.</div>
                                <div style="text-align: center; margin-top: 10px;">
                                    <mu-button @click.native="build()">Build</mu-button>
                                    <mu-button @click.native="build(2)" style="margin-left: 10px;">Build a json</mu-button>
                                    <mu-button @click.native="build(1)" style="margin-left: 10px;">Build to "doc"</mu-button>
                                </div>
                            </div>
                        </div>
                        <!-- Config -->
                        <div class="tab__panel__item" v-else-if="tab == 3">
                            <div style="padding-bottom: 10px;">Please place the following on "etc/__mutton__.php" to use this portal.</div>
                            <div class="textbox"><textarea class="textbox__in" rows="10" readonly v-model="configTxt" style="resize: none;"></textarea></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- alert -->
    <div v-if="alert!=''" class="alert">
        <div class="window">
            <div class="window__in">
                <div class="window__title">Alert</div>
                <div class="window__panel" style="min-width: 250px;">{{alert}}</div>
                <div class="window__controls">
                    <mu-button @click.native="alert=''">Ok</mu-button>
                </div>
            </div>
        </div>
    </div>
    <!-- mask -->
    <div v-if="mask" class="mask"></div>
</div>
</body>
</html>