# 更新日誌

# 240620
- Update Bootstrap and KongUtil.
- Prevent XSS.

# v1.2.3
230601
- 登入後導回原本的頁面。


# v1.2.2
230503
- 前台網址標準化：引入 `<link name="canonical">` 。
- 用 `<meta name="referrer">` 設定 Referrer Policy 。


# v1.2.1
230425
- 重寫了 `http_post()` ，可適用不同的 `Content-Type` 。
- 一些關於錯誤頁面的除錯。


# v1.2.0
230425
- 寫了 `error_output()` ，同時適用：
  - `.htaccess` 導過來的 403 和 404
  - 可自訂針對不同的 MIME type 輸出不同的格式
  - 其他頁面呼叫、輸出錯誤訊息或頁面


# v1.1.1
230424
- 用 `.htaccess` 防止盜連，並指定錯誤處理頁面。


# v1.0.0
230422
