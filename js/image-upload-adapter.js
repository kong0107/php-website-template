/**
 * image upload interface which also could be used as an extra plugin for *CKEditor 5*.
 * * Each instance cannot handle mulitple files at the same time.
 * * Images are scaled down if either width or height is larger than the specified size.
 * @see [CKEditor 5: Custom upload adapter]{@link https://ckeditor.com/docs/ckeditor5/latest/framework/guides/deep-dive/upload-adapter.html}
 * @see [CKEditor 5: Interface UploadAdapter]{@link https://ckeditor.com/docs/ckeditor5/latest/api/module_upload_filerepository-UploadAdapter.html}
 */
class ImageUploadAdapter {
    static counter = 0;

    /**
     * @param {ckeditor5/FileLoader} [loader] - required for *CKEditor 5*.
     */
    constructor(loader) {
        this.loader = loader;
        this.abortController = new AbortController();
    }

    /**
     * Implements `abort()` for `UploadAdapter` of *CKEditor 5*.
     * @see [MDN: AbortSignal]{@link https://developer.mozilla.org/en-US/docs/Web/API/AbortSignal}
     */
    abort() {
        this.abortController.abort();
    }

    /**
     * Implements `upload()` for `UploadAdapter` of *CKEditor 5*.
     * Also could be used with assigned `file` without *CKEditor*.
     * @param {File} [file] - required if `loader` is not assigned during construction.
     * @returns {Promise.<Object>}
     */
    async upload(file) {
        if(!file) file = await this.loader.file;
        if(!file.type.startsWith('image'))
            throw new TypeError('Loaded file is not an image.');

        const body = new FormData();
        body.set('file', await this.constructor.scaleDown(file));
        body.set('counter', ++this.constructor.counter);
        const res = await fetch('admin/upload.php', {
            method: 'POST',
            body,
            signal: this.abortController.signal
        });
        return res.json();
    }

    /**
     * Resize the input blob only if either width or height is larger than the corresponding dimension;
     * otherwise returns the origin blob.
     * @param {Blob} origin
     * @param {integer} [width]
     * @param {integer} [height]
     * @param {string} [mimeType]
     * @param {float} [quality]
     * @returns {Promise.<Blob>}
     */
    static async scaleDown(
        origin,
        width = 1024,
        height = 1024,
        mimeType = 'image/jpeg',
        quality = 0.9
    ) {
        const bitmap = await createImageBitmap(origin);
        if(bitmap.width <= width && bitmap.height <= height)
            return origin;

        const ratio = bitmap.width / bitmap.height;
        if(width / height > ratio)
            width = Math.round(height * ratio);
        else height = Math.round(width / ratio);

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext('2d');
        context.drawImage(bitmap, 0, 0, width, height);

        return new Promise(resolve => {
            canvas.toBlob(resolve, mimeType, quality);
        });
    }
}
