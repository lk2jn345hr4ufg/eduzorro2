<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Tool;

/**
 * Study tools live under a language-only prefix (/{language}/tools) because a
 * calculator is the same in every region — one canonical URL per language
 * instead of one per region/language pair.
 */
class ToolController extends Controller
{
    /** /{language}/tools */
    public function index(Language $language)
    {
        $tools = Tool::active()->ordered()->get()->groupBy('category');

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('tools.tools')],
        ];

        return view('tools.index', compact('tools', 'breadcrumbs'));
    }

    /** /{language}/tools/{tool} */
    public function show(Language $language, Tool $tool)
    {
        abort_unless($tool->is_active, 404);

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('tools.tools'), 'url' => route('tools.index', [$language])],
            ['label' => $tool->translate('name')],
        ];

        return view('tools.show', compact('tool', 'breadcrumbs'));
    }
}
