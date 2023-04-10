kongUtil.use();

listen(document, 'DOMContentLoaded', () => {
    /**
     * 把有 itemtype 屬性的節點們的屬性補正。
     */
    $$('[itemtype]').forEach(item => {
        item.setAttribute('itemscope', '');
        let type = item.getAttribute('itemtype');
        if(!type.includes('/')) {
            type = 'https://schema.org/' + type;
            item.setAttribute('itemtype', type);
        }
    });
});


/**
 * @func loadMarkdownToElement
 * @desc 下載 markdown ，轉成 HTML 後塞進元素裡。
 * @param {string | URL | Request} source
 * @param {Element | string} target
 * @returns {undefined}
 */
const loadMarkdownToElement = (() => {
    let promise; // 只在需要的時候才動態載入外部資源。
    return function(source, target) {
        if(!promise) promise = new Promise((onload, onerror) => {
            document.head.append(createElementFromJsonML(
                ['script', {
                    src: 'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
                    onload, onerror
                }]
            ));
        });
        if(!(target instanceof Element)) target = $(target);
        Promise.all([fetchText(source), promise])
        .then(([md]) => target.innerHTML = marked.parse(md));
    }
})();
