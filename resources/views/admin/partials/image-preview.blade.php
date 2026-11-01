<figure class="admin-image-preview" x-show="imagePath" x-cloak>
    <img :src="previewUrl()" alt="" x-show="! imageError" @@error="imageError = true" @load="imageError = false">
    <span class="admin-image-preview__fallback" x-show="imageError">Preview unavailable</span>
    <figcaption>Image preview</figcaption>
</figure>