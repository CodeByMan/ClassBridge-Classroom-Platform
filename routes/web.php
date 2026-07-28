<?php

use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserBioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');
Route::view('/setup-required', 'errors.setup-required')->name('setup.required');

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/sort', [ContentController::class, 'sort'])
        ->middleware('throttle:content-filter')
        ->name('sort');

    Route::post('/group', [GroupController::class, 'store'])
        ->middleware('throttle:group-create')
        ->name('group.store');
    Route::get('/group/{group}', [GroupController::class, 'show'])->name('group.show');
    Route::delete('/group/{group}', [GroupController::class, 'destroy'])->name('group.destroy');

    Route::post('/group/join', [GroupUserController::class, 'store'])
        ->middleware('throttle:group-join')
        ->name('group.join');
    Route::post('/group/{group}/join', [GroupUserController::class, 'joinPublic'])
        ->middleware('throttle:group-join')
        ->name('group.join-public');
    Route::get('/group/{group}/user', [GroupUserController::class, 'index'])
        ->name('group.user.index');

    Route::scopeBindings()->group(function (): void {
        Route::post('/group/{group}/post', [PostController::class, 'store'])
            ->middleware('throttle:post-create')
            ->name('group.post.store');
        Route::get('/group/{group}/post/{post}', [PostController::class, 'show'])
            ->name('group.post.show');
        Route::put('/group/{group}/post/{post}', [PostController::class, 'update'])
            ->name('group.post.update');
        Route::delete('/group/{group}/post/{post}', [PostController::class, 'destroy'])
            ->name('group.post.destroy');
        Route::post('/group/{group}/post/{post}/finish', [PostController::class, 'finish'])
            ->middleware('throttle:post-submit')
            ->name('group.post.finish');
        Route::post('/group/{group}/post/{post}/submission', [AssignmentSubmissionController::class, 'store'])
            ->middleware('throttle:post-submit')
            ->name('group.post.submission.store');
        Route::get('/group/{group}/post/{post}/submission/{submission}/download', [AssignmentSubmissionController::class, 'download'])
            ->name('group.post.submission.download');
        Route::put('/group/{group}/post/{post}/submission/{submission}/grade', [AssignmentSubmissionController::class, 'grade'])
            ->name('group.post.submission.grade');
        Route::put('/group/{group}/post/{post}/submission/{submission}/return', [AssignmentSubmissionController::class, 'returnWork'])
            ->name('group.post.submission.return');
    });

    Route::scopeBindings()->group(function (): void {
        Route::post('/post/{post}/comment', [PostCommentController::class, 'store'])
            ->middleware('throttle:comment-create')
            ->name('post.comment.store');
        Route::put('/post/{post}/comment/{comment}', [PostCommentController::class, 'update'])
            ->name('post.comment.update');
        Route::delete('/post/{post}/comment/{comment}', [PostCommentController::class, 'destroy'])
            ->name('post.comment.destroy');
    });

    Route::get('/user/{user}', [UserController::class, 'show'])->name('user.show');
    Route::put('/user/{user}/bio', [UserBioController::class, 'update'])->name('user.bio.update');
    Route::put('/user/{user}/image', [UserImageController::class, 'update'])
        ->middleware('throttle:profile-image')
        ->name('user.image.update');
});

Route::fallback(fn () => response()->view('notfound', [], 404));
