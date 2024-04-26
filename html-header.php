<?php
require_once __DIR__ . '/lib/start.php';

// 本頁資訊，有可能先被設定過了。
if(empty($page_info)) $page_info = [];

/**
 * og:url and canonical href
 * 本頁的絕對路徑，用於 og:url 和 canonical ，分別是 Facebook 和 Google 辨認「雖然不同連結不太一樣，但其實是同一個網頁」的關鍵。
 * 預設依照 $Get 整理；參數的順序可能是重要的。
 * https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls?hl=zh-tw
 * https://www.cnblogs.com/jianmingyuan/p/11049055.html
 */
if (! str_contains($_SERVER['PHP_SELF'], 'error.php')) {
    $origin = substr(CONFIG['site.root'], 0, strpos(CONFIG['site.root'], '/', 10));
    $page_info['canonical'] = $origin . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (! $Get->empty()) $page_info['canonical'] .= "?$Get";
}

/**
 * og:image
 * 縮圖的絕對路徑，用於 og:image ，即貼在臉書時會出現的圖示。
 * 規範上可以多張，但先處理一張就好。
 */
if (isset($page_info['og:image']) && ! parse_url($page_info['og:image'], PHP_URL_SCHEME))
    $page_info['og:image'] = CONFIG['site.root'] . $page_info['og:image'];

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= CONFIG['language'] ?>" itemtype="WebPage">
<head>
    <meta charset="UTF-8">
    <meta property="og:locale" content="<?= CONFIG['locale'] ?>">
    <meta itemprop="inLanguage" content="<?= CONFIG['language'] ?>">
    <meta name="referrer" content="no-referrer-when-downgrade">

    <?php if(empty($page_info['title'])): ?>
        <title><?= CONFIG['site.name'] ?></title>
        <meta itemprop="headline" property="og:title" content="<?= CONFIG['site.name'] ?>">
        <meta property="og:type" content="website">
    <?php else: ?>
        <title><?= $page_info['title'] ?> - <?= CONFIG['site.name'] ?></title>
        <meta itemprop="headline" property="og:title" content="<?= $page_info['title'] ?>">
        <meta property="og:type" content="article">
    <?php endif; ?>
    <meta property="og:site_name" content="<?= CONFIG['site.name'] ?>">

    <?php if(!empty($page_info['description'])): ?>
        <meta itemprop="description" property="og:description" name="description"
            content="<?= $page_info['description'] ?>"
        >
    <?php endif; ?>

    <meta name="author" content="<?= CONFIG['site.name'] ?>">
    <meta name="creator" content="<?= CONFIG['powered_by'] ?>">

    <link rel="canonical" href="<?= $page_info['canonical'] ?>">
    <meta property="og:url" content="<?= $page_info['canonical'] ?>">
    <?php if (isset($page_info['og:image'])): ?>
        <meta itemprop="primaryImageOfPage" property="og:image" content="<?= $page_info['og:image'] ?>">
    <?php endif; ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= CONFIG['site.root'] ?>">
    <link rel="icon" href="https://fakeimg.pl/256x256/?font=noto&text=<?= urlencode(CONFIG['site.name']) ?>" referrerpolicy="origin">
    <link rel="apple-touch-icon" href="https://fakeimg.pl/256x256/?font=noto&text=<?= urlencode(CONFIG['site.name']) ?>" referrerpolicy="origin">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/kong-util@0.7.7/dist/all.js" referrerpolicy="origin"></script>
    <script src="assets/main.js?mtime=<?= filemtime('assets/main.js') ?>"></script>
    <link rel="stylesheet" href="assets/main.css?mtime=<?= filemtime('assets/main.css') ?>">

    <?= $page_info['html_head'] ?? '' ?>
</head>
<body>
    <div class="container">
        <header>
            <nav class="navbar">
                <a class="navbar-brand" href="./"><?= CONFIG['site.name'] ?></a>
                <div class="d-flex">
                    <menu class="nav mt-0">
                        <li class="nav-item"><a class="nav-link" href="about.php">關於</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">其他</a></li>
                        <?php if($Session->user): ?>
                            <li class="nav-item">
                                <a class="nav-link d-flex" href="login.php?logout=1"
                                    title="<?= $Session->user->givenName ?> <?= $Session->user->familyName ?> &lt;<?= $Session->user->email ?>&gt;"
                                >登出</a>
                            </li>
                        <?php elseif(CONFIG['google.id']): ?>
                            <?php
                                /**
                                 * 使用 Google 商標是有限制的
                                 * https://developers.google.com/identity/branding-guidelines
                                 */
                            ?>
                            <link rel="preconnect" href="https://fonts.googleapis.com" referrerpolicy="origin">
                            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin referrerpolicy="origin">
                            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" referrerpolicy="origin">
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
            <?php if(!empty($page_info['breadcrumb_list'])): ?>
                <nav aria-label="導覽標記">
                    <ol itemprop="breadcrumb" itemtype="BreadcrumbList"
                        class="breadcrumb"
                    >
                        <?php foreach($page_info['breadcrumb_list'] as $index => $bc): ?>
                            <li itemprop="itemListElement" itemtype="ListItem"
                                class="breadcrumb-item"
                                <?php if(empty($bc['url'])): ?>
                                    aria-current="page"
                                <?php endif; ?>
                            >
                                <?php if(empty($bc['url'])): ?>
                                    <span itemprop="name"><?= $bc['name'] ?></span>
                                <?php else: ?>
                                    <a itemprop="item" href="<?= $bc['url'] ?>">
                                        <span itemprop="name"><?= $bc['name'] ?></span>
                                    </a>
                                <?php endif; ?>
                                <meta itemprop="position" content="<?= ($index + 1) ?>">
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </header>
        <main itemprop="mainContentOfPage"
            itemtype="<?= $page_info['itemtype'] ?? 'WebPageElement' ?>"
            class="my-2 pt-2 pb-5 border-top border-bottom"
        >
