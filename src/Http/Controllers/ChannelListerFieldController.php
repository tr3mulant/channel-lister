<?php

namespace IGE\ChannelLister\Http\Controllers;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

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
        $validated = $request->validate([
            'ordering' => 'required|integer|min:1',
            'field_name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'tooltip' => 'nullable|string|max:1000',
            'example' => 'nullable|string|max:255',
            'marketplace' => 'required|string|max:100',
            'input_type' => 'required|string|max:50',
            'input_type_aux' => 'nullable|string|max:1000',
            'required' => 'boolean',
            'grouping' => 'required|string|max:100',
            'type' => 'required|string|max:50',
        ]);

        $field = DB::transaction(fn () => ChannelListerField::create($validated));

        return redirect()->route('channel-lister-field.index')
            ->with('success', 'Channel Lister Field created successfully.');
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
        $field = ChannelListerField::query()->findOrFail($id);

        $validated = $request->validate([
            'ordering' => 'required|integer|min:1',
            'field_name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'tooltip' => 'nullable|string|max:1000',
            'example' => 'nullable|string|max:255',
            'marketplace' => 'required|string|max:100',
            'input_type' => 'required|string|max:50',
            'input_type_aux' => 'nullable|string|max:1000',
            'required' => 'boolean',
            'grouping' => 'required|string|max:100',
            'type' => 'required|string|max:50',
        ]);

        DB::transaction(fn () => $field->update($validated));

        return redirect()->route('channel-lister-field.index')
            ->with('success', 'Channel Lister Field updated successfully.');
    }

    /**
     * Logic to delete a channel lister field
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $field = ChannelListerField::query()->findOrFail($id);

        DB::transaction(fn () => $field->delete());

        return redirect()->route('channel-lister-field.index')
            ->with('success', 'Channel Lister Field deleted successfully.');
    }
}
