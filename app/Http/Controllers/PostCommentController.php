<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;

class PostCommentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Comment::class, 'comment');
    }

    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = new Comment($request->validated());
        $comment->user()->associate($request->user());
        $post->comments()->save($comment);

        return back()->with('success', 'Comment added successfully.');
    }

    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return back()->with('success', 'Comment updated successfully.');
    }

    public function destroy(Post $post, Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}
