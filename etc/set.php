<?php

// --- ROUTE ---

const ROUTE = [
    '@'         => ['path' => 'main', 'class' => '\\main\\main', 'action' => 'main'],
    'article/*' => ['path' => 'main', 'class' => '\\main\\main', 'action' => 'article']
];

// --- BASE ---

const STATIC_VER = '20170205121132';
const TIMEZONE = 'Asia/Shanghai';
const MUST_HTTPS = false;

// --- STATIC ---

// const STATIC_PATH = 'http://static.hanguoshuai.com/';
const STATIC_PATH = IMG_PATH;

