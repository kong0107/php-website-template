<?php
    // 若已被引用過，並不會重複引用。
    require_once './lib/start.php';
    header('Content-Type: text/html; charset=UTF-8');

    // 本頁資訊，有可能先被設定過了。
    if(empty($page_info)) $page_info = [];

    /**
     * $page_info['origin']
     * 本頁通訊協定及網域，用於生成絕對路徑，也可用於確認同源政策。
     * 只是暫時變數，用於生成其他變數的。
     */
    $page_info['origin'] = (empty($_SERVER['HTTPS']) ? 'http' : 'https')
        . '://' . $_SERVER['SERVER_NAME']
    ;

    /**
     * $page_info['og:url']
     * 本頁的絕對路徑，用於 og:url ，是臉書辨認「雖然不同連結不太一樣，但其實是同一個網頁」的關鍵。
     * 預設依照 $Get 整理；參數的順序可能是重要的。
     * https://www.cnblogs.com/jianmingyuan/p/11049055.html
     */
    $page_info['og:url'] = $page_info['origin']
        . $_SERVER['SCRIPT_NAME']
        . ($Get->empty() ? '' : "?$Get")
    ;

    /**
     * $page_info['og:image']
     * 縮圖的絕對路徑，用於 og:image ，即貼在臉書時會出現的圖示。
     * 規範上可以多張，但先處理一張就好。
     */
    if(empty($page_info['og:image'])) {
        $page_info['og:image'] = 'https://fakeimg.pl/1200x630/282828/eae0d0/?font=noto&text=';
        if(isset($page_info['title'])) $page_info['og:image'] .= $page_info['title'] . '%0a';
        $page_info['og:image'] .= CONFIG['site.name'];
    }
    if(!parse_url($page_info['og:image'], PHP_URL_SCHEME)) {
        $page_info['og:image'] = $page_info['origin']
            . abspath(dirname($_SERVER['SCRIPT_NAME']) . '/' . $page_info['og:image']);
    }

    if(in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
        echo '<!--' . chr(10);
        var_dump($_SESSION);
        var_dump(apache_request_headers());
        echo chr(10) . '-->';
    }
?>
<!DOCTYPE html>
<html lang="<?= CONFIG['language'] ?>" itemtype="WebPage">
<head>
    <meta charset="UTF-8">
    <meta property="og:locale" content="<?= CONFIG['locale'] ?>">
    <meta itemprop="inLanguage" content="<?= CONFIG['language'] ?>">

    <?php if(empty($page_info['title'])): ?>
        <title><?= CONFIG['site.name'] ?></title>
        <meta itemprop="headline" property="og:title" content="<?= CONFIG['site.name'] ?>">
        <meta property="og:type" content="website">
    <?php else: ?>
        <title><?= $page_info['title'] ?> - <?= CONFIG['site.name'] ?></title>
        <meta itemprop="headline" property="og:title" content="<?= $page_info['title'] ?>">
        <meta property="og:type" content="article">
    <?php endif; ?>
    <meta property="og:CONFIG['site.name']" content="<?= CONFIG['site.name'] ?>">

    <?php if(!empty($page_info['description'])): ?>
        <meta itemprop="description" property="og:description" name="description"
            content="<?= $page_info['description'] ?>"
        >
    <?php endif; ?>

    <meta name="author" content="<?= CONFIG['site.name'] ?>">
    <meta name="creator" content="<?= CONFIG['powered_by'] ?>">
    <meta property="og:url" content="<?= $page_info['og:url'] ?>">
    <meta itemprop="primaryImageOfPage" property="og:image" content="<?= $page_info['og:image'] ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://fakeimg.pl/256x256/?font=noto&text=<?= urlencode(CONFIG['site.name']) ?>">
    <link rel="apple-touch-icon" href="https://fakeimg.pl/256x256/?font=noto&text=<?= urlencode(CONFIG['site.name']) ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kong-util@0.7.2/dist/all.js"></script>
    <script src="assets/main.js?mtime=<?= stat('assets/main.js')['mtime'] ?>"></script>
    <link rel="stylesheet" href="assets/main.css?mtime=<?= stat('assets/main.css')['mtime'] ?>">
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
                            <li class="nav-item border rounded">
                                <a class="nav-link google-login" href="login.php">
                                    <img alt="Google" src="assets/google.svg" class="align-text-top">
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
