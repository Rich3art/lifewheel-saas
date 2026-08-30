<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Cms\PagePublisher;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function show(string $slug, PagePublisher $publisher): View
    {
        $page = Page::query()->where('slug', $slug)->with('currentVersion')->firstOrFail();
        $version = $publisher->publicVersion($page);

        abort_unless($version, Response::HTTP_NOT_FOUND);

        return view('pages.show', [
            'page' => $page,
            'version' => $version,
        ]);
    }
}
