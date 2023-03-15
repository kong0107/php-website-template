<?php
    require_once 'authentication.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?>管理後臺</title>
    <base href="../"><!-- 為了圖片的相對路徑。注意也因此： HTML 和 JS 用的路徑基準是這個，但在 PHP 則不是。 -->
    <link rel="icon" href="img/black.svg">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kong-util@0.6.8/dist/all.js"></script>
    <script src="js/main.js?time=<?=time()?>"></script>
    <link rel="stylesheet" href="css/main.css?time=<?=time()?>">
</head>
<body>
    <div class="container">
        <header>
            <nav class="navbar">
                <a class="navbar-brand" href="admin/"><?= SITE_NAME ?>管理後臺</a>
                <menu class="nav mt-0">
                    <li class="nav-item"><a class="nav-link" href="admin/markdown.php">文檔</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/product.php">產品</a></li>
                    <!-- <li class="nav-item"><a class="nav-link" href="admin/image.php">圖片</a></li> -->
                    <li class="nav-item"><a class="nav-link" href="." target="_blank">前臺</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php?logout=1">登出</a></li>
                </menu>
            </nav>
            <?php if(!empty($breadcrumb_list)): ?>
                <nav aria-label="導覽標記">
                    <ol class="breadcrumb">
                        <?php foreach($breadcrumb_list as $index => $bc): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $bc['url'] ?>"><?= $bc['name'] ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </header>
        <main class="my-2 pt-2 pb-5 border-top border-bottom">
