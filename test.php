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
if (! in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
	http_response_code(403);
	exit;
}

$use_composer = is_file('composer.json');
$config = parse_ini_file('var/config.ini');
if ($config) $log_dir = $config['log_dir'] ? $config['log_dir'] : 'var/logs/';
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
	<meta charset="UTF-8">
	<meta name="referrer" content="strict-origin-when-cross-origin">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>網站設定測試</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0">
</head>
<body class="container">
	<h1 class="my-3">網站設定測試</h1>
	<ul class="list-unstyled">
	    <li aria-label="設定檔" class="d-flex">
	        <?php if ($config): ?>
	            <span class="material-symbols-outlined text-success"
	            >check</span>
	            <p>成功載入設定檔，網站名稱為 <mark><?= $config['site.name'] ?></mark>。</p>
	        <?php else: ?>
	            <span class="material-symbols-outlined text-danger"
	            >cancel</span>
	            <p>
	                找不到設定檔。請將
	                <code>var/config.ini-sample</code>
	                複製為
	                <code>var/config.ini</code>
	                並修改後者內容。
	            </p>
	        <?php endif; ?>
	    </li>
	    <li aria-label="安裝套件" class="d-flex">
	        <?php if ($use_composer): ?>
	            <?php if (@ include './vendor/autoload.php'): ?>
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
	                    <code>composer install</code>
	                    。
	                </p>
	            <?php endif; ?>
	        <?php else: ?>
	            <span class="material-symbols-outlined text-success"
	            >check</span>
	            <p>本專案沒有使用 <code>Composer</code> 進行套件管理。</p>
	        <?php endif; ?>
	    </li>
	    <li aria-label="Session" class="d-flex">
	        <?php if (session_status() !== PHP_SESSION_DISABLED): ?>
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
	        <?php
	            if ($config):
	                if (@ opendir($log_dir)):
	                    closedir();
	                    if (strpos(realpath($log_dir), realpath(getenv('DOCUMENT_ROOT'))) === 0):
	                        ?>
	                            <div id="log_dir_message_info" class="d-flex">
	                                <span class="material-symbols-outlined text-info"
	                                >info</span>
	                                <p>記錄檔目錄可以運作，但仍建議將該目錄設定在 <code>DocumentRoot</code> 之外。</p>
	                            </div>
	                        <?php
	                    else:
	                        ?>
	                            <span class="material-symbols-outlined text-success"
	                            >check</span>
	                            <p>成功開啟記錄檔目錄。</p>
	                        <?php
	                    endif;
	                else:
	                    ?>
	                        <span class="material-symbols-outlined text-danger"
	                        >cancel</span>
	                        <p>
	                            未能開啟記錄檔目錄，請確認
	                            <code><?= $log_dir ?></code>
	                            目錄存在，
	                            並且 HTTP 伺服軟體有寫入權限。
	                        </p>
	                    <?php
	                endif;
	            else:
	                ?>
	                    <span class="material-symbols-outlined text-danger"
	                    >cancel</span>
	                    <p>未能知曉記錄檔路徑，需要先載入設定檔。</p>
	                <?php
	            endif;
	        ?>
	    </li>
	    <li aria-label="資料庫" class="d-flex">
	        <?php if (class_exists('mysqli')): ?>
	            <?php if ($config): ?>
	                <?php
	                    if ($config['mysqli.username']):
	                        try {
	                            $db = new mysqli(
	                                $config['mysqli.hostname'],
	                                $config['mysqli.username'],
	                                $config['mysqli.password'],
	                                $config['mysqli.database'],
	                                $config['mysqli.port']
	                            );
	                            $db->close();
	                            ?>
	                                <span class="material-symbols-outlined text-success"
	                                >check</span>
	                                <p>
	                                    成功以帳號
	                                    <code><?= $config['mysqli.username'] ?></code>
	                                    登入位於
	                                    <code><?= $config['mysqli.hostname'] ?></code>
	                                    的資料庫
	                                    <code><?= $config['mysqli.database'] ?></code>
	                                    。
	                                </p>
	                            <?php
	                        }
	                        catch (mysqli_sql_exception $e) {
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
	                        ?>
	                            <span class="material-symbols-outlined text-info"
	                            >info</span>
	                            <p>設定檔內未包含資料庫連線資訊。</p>
	                        <?php
	                    endif;
	                ?>
	            <?php else: ?>
	                <span class="material-symbols-outlined text-danger"
	                >cancel</span>
	                <p>未能知曉資料庫連線設定，需要先載入設定檔。</p>
	            <?php endif; ?>
	        <?php else: ?>
	            <span class="material-symbols-outlined text-danger"
	            >cancel</span>
	            伺服器未啟用 MySQLi 套件。
	        <?php endif; ?>
	    </li>
	</ul>
	<footer class="border-top pt-2">測試終了。</footer>
</body>
</html>
