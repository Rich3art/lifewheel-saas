<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => BlogPost::query()
                ->with('author', 'categories', 'tags')
                ->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->latest('published_at')
                ->paginate(10),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->with('author', 'categories', 'tags')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($post->isPublished(), Response::HTTP_NOT_FOUND);

        return view('blog.show', ['post' => $post]);
    }
}
