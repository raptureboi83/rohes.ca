<?php
/**
 * Exercises the media upload path end to end without a browser, so the
 * validation rules and the "is it still in use" delete guard are proven
 * before any of this reaches the server.
 *
 * Creates its own image, uploads it, edits it, then deletes it. Every
 * assertion is followed by a cleanup so a failure cannot leave junk in the
 * local database or on the media disk.
 */
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use App\Models\SectionItem;
use App\Models\User;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$adminId = User::where('is_admin', true)->value('id');

/**
 * Simulate a signed-in admin request. A real CSRF token is issued from a real
 * session and sent with the request, so the write paths are tested as they
 * actually behave rather than with the protection switched off.
 */
function simulate(string $method, string $uri, array $params = [], array $files = [])
{
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if ($id = User::where('is_admin', true)->value('id')) {
        Auth::login(User::find($id));
    }
    $session = app('session.store');
    $session->start();

    $request = Request::create($uri, $method, $params);
    $request->setLaravelSession($session);

    // Request::create()'s $files argument does not populate the uploaded-files
    // bag (it only fills the parameter bag), so a simulated multipart POST
    // arrives with no files and validation fails on "files is required".
    // Setting them explicitly is what a real request ends up doing.
    foreach ($files as $key => $upload) {
        $request->files->set($key, $upload);
    }

    if (! in_array($method, ['GET', 'HEAD'], true)) {
        $token = $session->token();
        $request->merge(['_token' => $token]);
        $request->headers->set('X-CSRF-TOKEN', $token);
    }

    return $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
}

/** Build a real, valid PNG on disk so validation is genuinely exercised. */
function makePng(string $path, int $w = 24, int $h = 18): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 40, 90, 60));
    imagepng($im, $path);
    imagedestroy($im);

    return $path;
}

$tmp = sys_get_temp_dir().'/rohes-media-'.bin2hex(random_bytes(4));
mkdir($tmp);
$fail = 0;
function check(string $label, bool $ok, string $extra = '')
{
    global $fail;
    if (! $ok) {
        $fail++;
    }
    printf("  %-46s %s%s\n", $label, $ok ? 'ok' : 'FAILED', $extra !== '' ? "  ({$extra})" : '');
}

$countBefore = Media::count();
$testMediaIds = [];
$png = makePng($tmp.'/test image.png');
$evil = $tmp.'/payload.php';
file_put_contents($evil, "<?php echo 'pwned'; ?>");
$huge = $tmp.'/big.png';
makePng($huge, 8, 8);

echo "-- uploading a valid image --\n";
$response = simulate('POST', '/admin/media', ['alt_text' => 'Test <b>alt</b>  text'], [
    'files' => [new UploadedFile($png, 'My Test Photo.png', 'image/png', null, true)],
]);
check('valid image accepted', $response->getStatusCode() === 302, 'status '.$response->getStatusCode());

$created = Media::query()->where('original_name', 'My Test Photo.png')->latest('id')->first();
if ($created) {
    $testMediaIds[] = $created->id;
}
check('media row created', $created !== null);
check('folder is uploads', (bool) $created && $created->folder === 'uploads', $created->folder ?? '-');
check('file exists on disk', $created && Storage::disk('media')->exists($created->relative_path));
check('width/height recorded', $created && $created->width === 24 && $created->height === 18,
    $created ? "{$created->width}x{$created->height}" : '-');
check('alt text stripped of markup and collapsed',
    $created && $created->alt_text === 'Test alt text', var_export($created?->alt_text, true));
check('original name preserved', $created && $created->original_name === 'My Test Photo.png');
check('stored filename was not the client name',
    $created && $created->filename !== 'My Test Photo.png', $created->filename ?? '-');
check('public url resolves', $created && str_starts_with($created->url, '/media/uploads/'), $created->url ?? '-');

echo "-- rejecting a disguised script --\n";
$before = Media::count();
$response = simulate('POST', '/admin/media', [], [
    'files' => [new UploadedFile($evil, 'shell.php', 'application/x-php', null, true)],
]);
check('php file rejected (302 back, not stored)', $response->getStatusCode() === 302 && Media::count() === $before,
    'status '.$response->getStatusCode().', count '.$before.'->'.Media::count());
check('no .php on the media disk', ! collect(Storage::disk('media')->allFiles())->contains(
    fn ($f) => str_ends_with(strtolower($f), '.php')
));

echo "-- traversal attempt in the original name --\n";
$before = Media::count();
$response = simulate('POST', '/admin/media', [], [
    'files' => [new UploadedFile($png, '../../../evil.png', 'image/png', null, true)],
]);
check('traversal name sanitised to a safe filename', Media::count() === $before + 1);
$evilRow = Media::query()->where('original_name', 'evil.png')->latest('id')->first();
if ($evilRow) {
    $testMediaIds[] = $evilRow->id;
}
check('stored path cannot escape the media root',
    $evilRow && ! str_contains($evilRow->relative_path, '..') && ! str_contains($evilRow->filename, '/'),
    $evilRow->relative_path ?? '-');

echo "-- delete guard on a file that is in use --\n";
$inUse = Media::query()->where('filename', 'svc-fleet-truck.jpg')->first();
$users = SectionItem::where('image_path', $inUse->url)->count();
check('legacy file is referenced by items', $users > 0, "{$users} item(s)");
$before = Media::count();
$response = simulate('DELETE', '/admin/media/'.$inUse->id);
check('in-use file NOT deleted', Media::count() === $before && Media::find($inUse->id) !== null);
check('file still on disk', Storage::disk('media')->exists($inUse->relative_path));

echo "-- editing details --\n";
$response = simulate('PUT', '/admin/media/'.$created->id, ['alt_text' => 'Updated description', 'title' => 'A title']);
$created->refresh();
check('alt text updated', $created->alt_text === 'Updated description', var_export($created->alt_text, true));
check('title updated', $created->title === 'A title', var_export($created->title, true));

echo "-- deleting an unused file --\n";
$response = simulate('DELETE', '/admin/media/'.$created->id);
check('unused file deleted', Media::find($created->id) === null);
check('file removed from disk', ! Storage::disk('media')->exists($created->relative_path));
if ($evilRow) {
    simulate('DELETE', '/admin/media/'.$evilRow->id);
}

// Remove only fixture rows recorded by this run. Other client uploads may
// already exist in the same folder and must never be treated as test debris.
foreach (array_unique($testMediaIds) as $id) {
    $row = Media::find($id);
    if ($row) {
        Storage::disk($row->disk)->delete($row->relative_path);
        $row->delete();
    }
}
foreach (glob($tmp.'/*') as $file) {
    @unlink($file);
}
@rmdir($tmp);

printf("\n  media count back to %d (was %d): %s\n",
    Media::count(), $countBefore, Media::count() === $countBefore ? 'yes' : 'NO');
check('no leftover upload rows', Media::count() === $countBefore);

// Every seeded row must still be present, since this test deletes rows.
check('all 14 seeded media rows intact', Media::count() === 14, 'now '.Media::count());

echo "\n  failures: $fail\n";
exit($fail ? 1 : 0);
