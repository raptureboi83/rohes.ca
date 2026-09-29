<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SectionItemController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
|
| One real URL per page, looked up by slug. This is the schema-driven
| renderer described in docs/CHANGELOG.md — no page is hardcoded.
|
*/

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/contact', [SiteController::class, 'page'])
    ->defaults('slug', 'contact')
    ->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Pages
        Route::resource('pages', PageController::class);
        Route::patch('pages/{page}/toggle/{field}', [PageController::class, 'toggle'])
            ->name('pages.toggle');

        // Sections, nested under their page
        Route::get('pages/{page}/sections', [SectionController::class, 'index'])
            ->name('pages.sections.index');
        Route::get('pages/{page}/sections/create', [SectionController::class, 'create'])
            ->name('pages.sections.create');
        Route::post('pages/{page}/sections', [SectionController::class, 'store'])
            ->name('pages.sections.store');
        Route::get('sections/{section}/edit', [SectionController::class, 'edit'])
            ->name('sections.edit');
        Route::put('sections/{section}', [SectionController::class, 'update'])
            ->name('sections.update');
        Route::delete('sections/{section}', [SectionController::class, 'destroy'])
            ->name('sections.destroy');
        Route::patch('sections/{section}/toggle/{field}', [SectionController::class, 'toggle'])
            ->name('sections.toggle');

        // Section items
        Route::get('sections/{section}/items', [SectionItemController::class, 'index'])
            ->name('sections.items.index');
        Route::get('sections/{section}/items/create', [SectionItemController::class, 'create'])
            ->name('sections.items.create');
        Route::post('sections/{section}/items', [SectionItemController::class, 'store'])
            ->name('sections.items.store');
        Route::get('sections/{section}/items/{item}/edit', [SectionItemController::class, 'edit'])
            ->name('sections.items.edit');
        Route::put('sections/{section}/items/{item}', [SectionItemController::class, 'update'])
            ->name('sections.items.update');
        Route::delete('sections/{section}/items/{item}', [SectionItemController::class, 'destroy'])
            ->name('sections.items.destroy');
        Route::patch('sections/{section}/items/{item}/move/{direction}', [SectionItemController::class, 'move'])
            ->name('sections.items.move');

        // Settings, media, messages, users
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::patch('messages/{message}/read', [MessageController::class, 'toggleRead'])
        ->name('messages.read');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::resource('users', UserController::class)->except(['show']);
    });

/*
|--------------------------------------------------------------------------
| Account (Breeze)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
     | Laravel's stock auth controllers (password confirmation, email
     | verification) redirect to a route named "dashboard". This app keeps the
     | CMS under the "admin." name prefix, so that name does not exist and every
     | one of those redirects would throw. This alias keeps them working and
     | sends administrators to the CMS, everyone else to the public home page.
     */
    Route::get('/dashboard', function () {
        return auth()->user()?->is_admin
            ? redirect()->route('admin.dashboard')
            : redirect()->route('home');
    })->name('dashboard');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Catch-all public page route
|--------------------------------------------------------------------------
|
| Must be registered LAST, after every named route, or it would swallow
| /admin, /login and the rest.
|
*/

Route::get('/{slug}', [SiteController::class, 'page'])->name('page');
