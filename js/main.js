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

    /**
     * 把 .markdown 裡的 Markdown 替代成 HTML 。
     * 只在需要的時候才動態載入外部資源。
     */
    if($('.markdown')) document.head.append(createElementFromJsonML(
        ['script', {
            src: 'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            crossorigin: 'anonymous',
            onload: () => {
                $$('.markdown').forEach(elem => {
                    elem.innerHTML = marked.parse(elem.innerHTML);
                    elem.classList.remove('markdown');
                });
            }
        }]
    ));
});
