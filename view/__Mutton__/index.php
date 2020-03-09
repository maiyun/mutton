<?php
/** @var bool $hasConfig */
/** @var string $local */
/** @var string $_xsrf */
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Mutton Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
    <link rel="stylesheet" href="<?php echo URL_STC ?>__Mutton__/index.css?<?php echo VER ?>">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.11/dist/vue.min.js"></script>
    <script>
        var tab = <?php echo $hasConfig ? '0' : '3' ?>;
        var local = '<?php echo $local ?>';
        var _xsrf = '<?php echo $_xsrf ?>';
        var URL_BASE = '<?php echo URL_BASE ?>';
        var __LOCALE_OBJ = <?php echo $__LOCALE_OBJ ?>;
    </script>
    <script src="<?php echo URL_STC ?>__Mutton__/index.js?<?php echo VER ?>"></script>
</head>
<body>
<div id="vue" :style="{'zoom': zoom}">
    <!-- 图标 -->
    <div style="display: inline-flex; flex-direction: column; align-items: center;">
        <div class="icon-out" tabindex="0" @dblclick="alert=l('System error.')">
            <div class="icon document"></div>
            <div class="icon__txt"><div class="icon__txt-in"><?php echo l('My Documents') ?></div></div>
        </div>
        <div class="icon-out" tabindex="0" @dblclick="alert=l('System error.')" style="margin-top: 28px;">
            <div class="icon computer"></div>
            <div class="icon__txt"><div class="icon__txt-in"><?php echo l('My Computer') ?></div></div>
        </div>
        <div class="icon-out" tabindex="0" @dblclick="alert=l('System error.')" style="margin-top: 28px;">
            <div class="icon recycle"></div>
            <div class="icon__txt"><div class="icon__txt-in"><?php echo l('Recycle Bin') ?></div></div>
        </div>
    </div>
    <!-- 窗体 -->
    <div style="position: absolute; left: 0; top: 20px; width: 100%; pointer-events: none;">
        <div class="window" style="max-width: 800px; margin: auto; pointer-events: initial;">
            <div class="window-in">
                <div class="window__title">Mutton Portal</div>
                <div class="window__panel">
                    <div class="title">Mutton Portal</div>
                    <div class="tab">
                        <div class="tab__top">
                            <div tabindex="0" class="tab__top__item-out" :class="{'selected': tab == 0}" @mousedown="tab = 0">
                                <div class="tab__top__item">
                                    <div class="tab__top__item-in"><?php echo l('Password') ?></div>
                                </div>
                            </div>
                            <div tabindex="0" class="tab__top__item-out" :class="{'selected': tab == 1}" @mousedown="tab = 1">
                                <div class="tab__top__item">
                                    <div class="tab__top__item-in"><?php echo l('Check') ?></div>
                                </div>
                            </div>
                            <div tabindex="0" class="tab__top__item-out" :class="{'selected': tab == 2}" @mousedown="tab = 2;getLocalLibs();">
                                <div class="tab__top__item">
                                    <div class="tab__top__item-in"><?php echo l('System') ?></div>
                                </div>
                            </div>
                            <div tabindex="0" class="tab__top__item-out" :class="{'selected': tab == 3}" @mousedown="tab = 3">
                                <div class="tab__top__item">
                                    <div class="tab__top__item-in"><?php echo l('Profile') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab__panel">
                            <!-- Password -->
                            <div class="tab__panel__item" v-if="tab == 0">
                                <div style="padding-bottom: 10px;"><?php echo l('Please enter your password:') ?></div>
                                <div class="textbox"><input class="textbox-in" type="password" v-model="password"></div>
                                <div style="padding-top: 10px;"><?php echo l('When the input is complete, you can use other features.') ?></div>
                                <mu-line></mu-line>
                                <?php echo l('Installation and access sources:') ?>
                                <div style="margin-top: 10px; text-align: center;">
                                    <mu-radio v-model="mirror" value="global"><?php echo l('Global') ?> (Github)</mu-radio>
                                    <mu-radio v-model="mirror" value="cn" style="margin-left: 20px;"><?php echo l('Mainland China') ?> (Gitee)</mu-radio>
                                </div>
                                <div style="margin-top: 10px;"><?php echo l('If the current server is in Mainland China, select "Mainland China" for better speed.') ?></div>
                                <mu-line></mu-line>
                                <div style="text-align: center;">
                                    <mu-button @click.native="window.location.href='?l=en'">English</mu-button>
                                    <mu-button @click.native="window.location.href='?l=zh-CN'" style="margin-left: 10px;">简体中文</mu-button>
                                    <mu-button @click.native="window.location.href='?l=zh-TW'" style="margin-left: 10px;">繁體中文</mu-button>
                                </div>
                            </div>
                            <!-- Check -->
                            <div class="tab__panel__item" v-else-if="tab == 1">
                                <div style="margin-bottom: 10px;"><?php echo l('Please click refresh and select the version below:') ?></div>
                                <mu-list :list="verList" v-model="verIndex"></mu-list>
                                <div style="text-align: center; margin-top: 10px;">
                                    <mu-button @click.native="refresh()"><?php echo l('Refresh') ?></mu-button>
                                    <mu-button @click.native="check()" style="margin-left: 10px;"><?php echo l('Check') ?></mu-button>
                                </div>
                                <div style="margin: 10px 0;"><?php echo l('Abnormal file:') ?></div>
                                <mu-list :list="infoList"></mu-list>
                            </div>
                            <!-- System -->
                            <div class="tab__panel__item" v-else-if="tab == 2">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div>
                                        <?php echo l('Current version:') ?> <?php echo VER ?><br>
                                        <?php echo l('Latest versions:') ?> {{latestVer}}<br>
                                    </div>
                                    <mu-button @click.native="refresh()" style="margin-top: 10px;"><?php echo l('Refresh') ?></mu-button>
                                </div>
                                <mu-line></mu-line>
                                <div style="display: flex;">
                                    <div style="flex: 1;">
                                        <?php echo l('Online library:') ?> {{selectedVer !== '' ? '(' + selectedVer + ')' : ''}}
                                        <mu-list :list="onlineLibs" v-model="onlineLibsIndex" style="margin-top: 10px;"></mu-list>
                                        <div style="margin-top: 10px;">
                                            <mu-button @click.native="install()"><?php echo l('Install/Reinstall') ?></mu-button>
                                        </div>
                                    </div>
                                    <div style="flex: 1; margin-left: 10px;">
                                        <?php echo l('Local library:') ?>
                                        <mu-list :list="localLibs" v-model="localLibsIndex" style="margin-top: 10px;"></mu-list>
                                        <div style="margin-top: 10px;">
                                            <mu-button @click.native="uninstall()"><?php echo l('Uninstall') ?></mu-button>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top: 10px;"><?php echo l('To get online library information, click the "Check" tab, select the appropriate version, and then click the "Check" button.') ?></div>
                                <mu-line></mu-line>
                                <div style="display: flex; flex-direction: column;">
                                    <?php echo l('Automatic upgrade (library will not be upgraded, the above is required to upgrade library):') ?><br>
                                    <mu-list :list="upgradeInfoList" style="margin-top: 10px;"></mu-list>
                                    <div style="margin-top: 10px; text-align: center;">
                                        <mu-button :class="{'disabled': selectedVer === '' || upgradeRunning}" @click.native="upgrade()"><?php echo l('Start upgrade') ?></mu-button>
                                    </div>
                                </div>
                                <mu-line></mu-line>
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div><?php echo l('Build a "mblob" file:') ?></div>
                                    <div style="text-align: center; margin-top: 10px;">
                                        <mu-button @click.native="build()"><?php echo l('Build') ?></mu-button>
                                        <mu-button @click.native="formatTld()" style="margin-left: 10px;"><?php echo l('Format tld') ?></mu-button>
                                    </div>
                                </div>
                            </div>
                            <!-- Profile -->
                            <div class="tab__panel__item" v-else-if="tab == 3">
                                <div style="padding-bottom: 10px;"><?php echo l('Please place the following on "etc/__mutton__.php" to use this portal.') ?></div>
                                <pre class="textbox"><code class="textbox-in" style="height: 300px;"><?php echo substr(highlight_string("<?php
const __MUTTON__PWD = 'Your password';", true), 6, -7) ?><br><br><br></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- alert -->
    <div v-if="alert!=''" class="alert">
        <div class="window">
            <div class="window-in">
                <div class="window__title"><?php echo l('Alert') ?></div>
                <div class="window__panel" style="min-width: 250px; max-width: 550px;">{{alert}}</div>
                <div class="window__controls">
                    <mu-button @click.native="alert=''"><?php echo l('OK') ?></mu-button>
                </div>
            </div>
        </div>
    </div>
    <!-- alert -->
    <div v-if="confirmTxt!=''" class="alert">
        <div class="window">
            <div class="window-in">
                <div class="window__title"><?php echo l('Confirm') ?></div>
                <div class="window__panel" style="min-width: 250px; max-width: 550px;">{{confirmTxt}}</div>
                <div class="window__controls">
                    <mu-button @click.native="confirmTxt='';confirmResolve(true);"><?php echo l('OK') ?></mu-button>
                    <mu-button @click.native="confirmTxt='';confirmResolve(false);" style="margin-left: 10px;"><?php echo l('Cancel') ?></mu-button>
                </div>
            </div>
        </div>
    </div>
    <!-- mask -->
    <div v-if="mask" class="mask"></div>
</div>
</body>
</html>