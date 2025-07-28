<?php

namespace IGE\ChannelLister\Http\Controllers;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelListerFieldController extends Controller
{
    /**
     * Logic to list all channel lister fields
     */
    public function index(): View
    {
        return view('channel-lister::channel-lister-field.index', [
            'fields' => ChannelListerField::all(),
        ]);
    }

    /**
     * Logic to show form for creating a new channel lister field
     */
    public function create(): View
    {
        return view('channel-lister::channel-lister-field.create');
    }

    /**
     * Logic to store a new channel lister field
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('channel-lister-field.index');
    }

    /**
     * Logic to show a specific channel lister field
     */
    public function show(string|int $id): View
    {
        return view('channel-lister::channel-lister-field.show', [
            'field' => ChannelListerField::query()->findOrFail($id),
        ]);
    }

    /**
     * Logic to show form for editing an existing channel lister field
     */
    public function edit(string|int $id): View
    {
        return view('channel-lister::channel-lister-field.edit', [
            'field' => ChannelListerField::query()->findOrFail($id),
        ]);
    }

    /**
     * Logic to update an existing channel lister field
     */
    public function update(Request $request, string|int $id): RedirectResponse
    {
        return redirect()->route('channel-lister-field.index');
    }

    /**
     * Logic to delete a channel lister field
     */
    public function destroy(string|int $id): RedirectResponse
    {
        return redirect()->route('channel-lister-field.index');
    }
}
