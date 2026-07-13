<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::withCount('nodes')->orderBy('short_code')->get();

        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'short_code' => 'required|string|max:60|unique:locations,short_code',
            'description' => 'nullable|string',
        ]);

        $location = Location::create($validated);

        return redirect()->route('locations.index')->with('success', "Location '{$location->short_code}' dibuat.");
    }

    public function edit(Location $location)
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'short_code' => ['required', 'string', 'max:60', Rule::unique('locations', 'short_code')->ignore($location->id)],
            'description' => 'nullable|string',
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', "Location '{$location->short_code}' diupdate.");
    }

    public function destroy(Location $location)
    {
        if ($location->nodes()->exists()) {
            return back()->withErrors(['delete' => 'Location ini masih dipakai node, pindahin dulu.']);
        }

        $code = $location->short_code;
        $location->delete();

        return redirect()->route('locations.index')->with('success', "Location '{$code}' dihapus.");
    }
}
