<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContentController extends Controller
{
    public function sort(Request $request): string|Response
    {
        if (! $request->ajax()) {
            return response('AJAX request required.', 400);
        }

        $validated = $request->validate([
            'sort' => ['nullable', 'integer', 'between:1,6'],
            'groups' => ['nullable', 'integer', 'between:1,3'],
            'search' => ['nullable', 'string', 'max:100'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $userId = $request->user()->id;
        $groupId = isset($validated['group_id']) ? (int) $validated['group_id'] : null;
        $groupFilter = (int) ($validated['groups'] ?? 1);
        $search = trim((string) ($validated['search'] ?? ''));
        $sort = (int) ($validated['sort'] ?? ($groupId ? 2 : 4));

        if ($groupId) {
            $group = Group::findOrFail($groupId);
            $this->authorize('view', $group);
        }

        $query = Post::query()
            ->with([
                'group:id,name,public',
                'postusers' => fn ($submissionQuery) => $submissionQuery->where('user_id', $userId),
            ])
            ->when(
                $groupId,
                fn (Builder $postQuery) => $postQuery->where('group_id', $groupId),
                function (Builder $postQuery) use ($groupFilter, $userId): void {
                    $postQuery->whereHas('group', function (Builder $groupQuery) use ($groupFilter, $userId): void {
                        $groupQuery->whereHas('users', fn (Builder $memberQuery) => $memberQuery->whereKey($userId));

                        if ($groupFilter === 2) {
                            $groupQuery->where('public', false);
                        } elseif ($groupFilter === 3) {
                            $groupQuery->where('public', true);
                        }
                    });
                }
            )
            ->when($search !== '', function (Builder $postQuery) use ($search): void {
                $postQuery->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            });

        $this->applySort($query, $sort);

        $posts = $query->paginate(6)->withQueryString();

        return response(view('app.post.index', ['posts' => $posts])->render());
    }

    private function applySort(Builder $query, int $sort): void
    {
        match ($sort) {
            1 => $query->oldest(),
            2 => $query->latest(),
            3 => $query->orderByRaw('deadline IS NULL')->orderByDesc('deadline'),
            4 => $query->orderByRaw('deadline IS NULL')->orderBy('deadline'),
            5 => $query->orderBy('name'),
            6 => $query->orderByDesc('name'),
            default => $query->latest(),
        };
    }
}
