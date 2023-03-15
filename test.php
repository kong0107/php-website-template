<?php
    /**
     * 只在由本機連線時才能使用此頁。
     *
     * > $_SERVER['REMOTE_ADDR'] contains the real IP address of the connecting party. That is the most reliable value you can find.
     * > However, they can be behind a proxy server in which case the proxy may have set the $_SERVER['HTTP_X_FORWARDED_FOR'], but this value is easily spoofed.
     *
     * > REMOTE_ADDR might not contain the real IP of the TCP connection. This entirely depends on your SAPI.
     * --- https://stackoverflow.com/questions/3003145/#3003233
     */
    if(!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
        http_response_code(403);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>網站設定測試</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0">
</head>
<body class="container">
    <h1 class="my-3">網站設定測試</h1>
    <ul class="list-unstyled">
        <li aria-label="設定檔" class="d-flex">
            <?php if(@ include './include/config.php'): ?>
                <span class="material-symbols-outlined text-success"
                >check</span>
                <p>成功載入設定檔，網站名稱為 <mark><?= SITE_NAME ?></mark>。</p>
            <?php else: ?>
                <span class="material-symbols-outlined text-danger"
                >cancel</span>
                <p>
                    找不到設定檔。請將
                    <code>include/config.sample.php</code>
                    複製為
                    <code>include/config.php</code>
                    並修改後者內容。
                </p>
            <?php endif; ?>
        </li>
        <!-- <li aria-label="安裝套件" class="d-flex">
            <?php if(@ include './vendor/autoload.php'): ?>
                <span class="material-symbols-outlined text-success"
                >check</span>
                <p>成功載入套件。</p>
            <?php else: ?>
                <span class="material-symbols-outlined text-danger"
                >cancel</span>
                <p>
                    找不到套件。請
                    <a href="https://getcomposer.org/">安裝 Composer</a>，
                    然後在
                    <code><?= __DIR__ ?></code>
                    執行
                    <code>composer update</code>
                    。
                </p>
            <?php endif; ?>
        </li> -->
        <li aria-label="Session" class="d-flex">
            <?php if(session_status() !== PHP_SESSION_DISABLED): ?>
                <span class="material-symbols-outlined text-success"
                >check</span>
                <p>可以啟用 Session 功能。</p>
            <?php else: ?>
                <span class="material-symbols-outlined text-danger"
                >cancel</span>
                <p>
                    未能啟用 PHP 的 Session 功能。
                    請嘗試依
                    <a href="https://stackoverflow.com/questions/32356373/#32362840">此篇</a>
                    所提方法強制開啟，
                    或重新編譯 HTTP 伺服軟體。
                </p>
            <?php endif; ?>
        </li>
        <li aria-label="記錄檔" class="d-flex">
            <?php if(defined('LOG_DIR')): ?>
                <?php
                    if(@ opendir(LOG_DIR)):
                        closedir();
                        if(strpos(realpath(LOG_DIR), realpath(getenv('DOCUMENT_ROOT'))) === 0):
                            if(is_file(LOG_DIR . '/.htaccess')):
                            ?>
                                <span class="material-symbols-outlined text-info"
                                >info</span>
                                <p>記錄檔目錄可以運作，但仍建議將該目錄設定在 <code>DocumentRoot</code> 之外。</p>
                            <?php
                            else:
                            ?>
                                <span class="material-symbols-outlined text-warning"
                                >warning</span>
                                <p>
                                    若將記錄檔目錄設定在
                                    <code>DocumentRoot</code>
                                    之內，請務必在記錄檔目錄內放置適當的
                                    <code>.htaccess</code>
                                    檔案，以避免純文字的記錄檔被訪客讀取。
                                    例如這個<a href="<?= LOG_DIR ?>/.htacces">連結</a>
                                    應該回傳 <code>403 Forbidden</code> ，
                                    而不是 <code>404 Not Found</code> 或 <code>200 OK</code>。
                                </p>
                            <?php
                            endif;
                        else: ?>
                            <span class="material-symbols-outlined text-success"
                            >check</span>
                            <p>成功開啟記錄檔目錄。</p>
                        <?php
                        endif;
                    ?>
                <?php else: ?>
                    <span class="material-symbols-outlined text-danger"
                    >cancel</span>
                    <p>
                        未能開啟記錄檔目錄，請確認
                        <code><?= realpath(LOG_DIR) ?></code>
                        目錄存在，
                        並且 HTTP 伺服軟體有寫入權限。
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <span class="material-symbols-outlined text-danger"
                >cancel</span>
                <p>未能知曉記錄檔路徑，需要先載入設定檔。</p>
            <?php endif; ?>
        </li>
        <li aria-label="資料庫" class="d-flex">
            <?php if(class_exists('mysqli')): ?>
                <?php
                    if(defined('DB_NAME')):
                        try {
                            $db = new mysqli(DB_HOST, DB_NAME, DB_PASS, DB_NAME);
                            $db->close();
                            ?>
                                <span class="material-symbols-outlined text-success"
                                >check</span>
                                <p>
                                    成功以帳號
                                    <code><?= DB_USER ?></code>
                                    登入位於
                                    <code><?= DB_HOST ?></code>
                                    的資料庫
                                    <code><?= DB_NAME ?></code>
                                    。
                                </p>
                            <?php
                        } catch (mysqli_sql_exception $e) {
                            $errno = $e->getCode();
                            ?>
                                <span class="material-symbols-outlined text-danger"
                                >cancel</span>
                                <p>
                                    未能登入資料庫。<br>
                                    <a href="https://google.com/search?q=%22MySQL+error+<?= $errno ?>%22"
                                    >MySQL Error <?= $errno ?></a>
                                    <?= $e->getMessage() ?>
                                </p>
                            <?php
                        }
                    else:
                        ?><p>未能知曉資料庫連線設定，需要先載入設定檔。</p><?php
                    endif;
                ?>
            <?php else: ?>
                <span class="material-symbols-outlined text-danger"
                >cancel</span>
                伺服器未啟用 MySQLi 套件。
            <?php endif; ?>
        </li>
    </ul>
    <footer>測試終了。</footer>
</body>
</html>
