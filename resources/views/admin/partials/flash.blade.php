{{-- Flash messages. `error` is used for blocked destructive actions;
     `success` for everything that worked. --}}
@if (session('success'))
    <div class="flash flash--success" role="status">
        <span>{{ session('success') }}</span>
        <button type="button" class="flash__close" aria-label="Dismiss">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="flash flash--error" role="alert">
        <span>{{ session('error') }}</span>
        <button type="button" class="flash__close" aria-label="Dismiss">&times;</button>
    </div>
@endif

@if ($errors->any() && ! $errors->has('__flash_suppressed'))
    <div class="flash flash--error" role="alert">
        <span><b>There is a problem.</b> Please check the highlighted fields below.</span>
        <button type="button" class="flash__close" aria-label="Dismiss">&times;</button>
    </div>
@endif

<script>
    document.querySelectorAll('.flash__close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.flash').remove();
        });
    });
</script>
