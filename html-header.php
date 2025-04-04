<?php
require_once __DIR__ . '/lib/init.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW" itemtype="WebPage">
<head>
	<meta charset="UTF-8">
	<meta property="og:locale" content="zh_TW">
	<meta itemprop="inLanguage" content="zh-Hant-TW">

	<title></title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" referrerpolicy="origin"></script>
	<script src="https://cdn.jsdelivr.net/npm/kong-util@0.7.12/dist/all.js" referrerpolicy="origin"></script>
	<link rel="stylesheet" href="assets/main.css?<?= filemtime('assets/main.css') ?>">
</head>
<body>
	<script src="assets/afterbegin.js?<?= filemtime('assets/afterbegin.js') ?>"></script>
	<div class="container">
	    <header>
	        <nav class="navbar">
	            <a class="navbar-brand" href="./">SiteName</a>
	            <div class="d-flex">
	                <menu class="nav mt-0">
	                    <?php if (isset($current_user)): ?>
	                        <li class="nav-item">
	                            <a class="nav-link" href="login.php?logout=1">
	                                登出
	                                <span title="<?= $current_user->email ?>"><?= $current_user->name ?></span>
	                            </a>
	                        </li>
	                    <?php else: ?>
	                        <li class="nav-item border rounded">
	                            <a class="nav-link google-login" href="login.php">
	                                <img alt aria-hidden="true" src="assets/google.svg" class="align-text-top">
	                                <span class="d-none d-md-inline">使用 Google</span>
	                                登入
	                            </a>
	                        </li>
	                    <?php endif; ?>
	                </menu>
	            </div>
	        </nav>
	    </header>
	    <main itemprop="mainContentOfPage"
	        itemtype="WebPageElement"
	        class="my-2 pt-2 pb-5 border-top border-bottom"
	    >
