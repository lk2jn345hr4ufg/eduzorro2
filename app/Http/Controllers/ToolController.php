<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Region;
use App\Models\Tool;

class ToolController extends Controller
{
    /** /{region}/{lang}/tools — list of study tools. */
    public function index(Region $region, Language $language)
    {
        $tools = Tool::active()->ordered()->get()->groupBy('category');

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('tools.tools')],
        ];

        return view('tools.index', compact('tools', 'breadcrumbs'));
    }

    /** /{region}/{lang}/tools/{tool} — a single tool. */
    public function show(Region $region, Language $language, Tool $tool)
    {
        abort_unless($tool->is_active, 404);

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('tools.tools'), 'url' => route('tools.index', [$region, $language])],
            ['label' => $tool->translate('name')],
        ];

        return view('tools.show', compact('tool', 'breadcrumbs'));
    }
}
