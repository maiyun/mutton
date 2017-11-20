<?php

// --- ROUTE ---

const ROUTE = [
    '@'                             => 'main/main',
    'article/([0-9]+?)'             => 'main/article'
];

// --- BASE ---

const STATIC_VER = '20170205121132';
const TIMEZONE = 'Asia/Shanghai';
const MUST_HTTPS = false;
const CACHE_TTL = 0;

// --- STATIC ---

// const STATIC_PATH = 'http://static.hanguoshuai.com/';
const STATIC_PATH = IMG_PATH;

