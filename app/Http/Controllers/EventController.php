<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

        $events = Event::paginate(10); // Use pagination to avoid loading too many records at once

        return view('events.index', compact('events'));
    }


    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

        $events = Event::paginate(10); // Use pagination to avoid loading too many records at once

        return view('events.list', compact('events'));
    }

    
    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {

        $event = new Event;
        return view('events.create', compact('event'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('eventBanners', 'public');
            $data['banner'] = $bannerPath;
        }
        
        Event::create($data);

        // Redirect to the create page with a success message
        return redirect()->route('events.list')
            ->with('status', 'Event created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Event $event): View
    {
        return view('events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): View
    {
        return view('events.edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EventUpdateRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('banners', 'public');
            $data['banner'] = $bannerPath;
        }
        
        $event->update($data);

        return redirect()->route('events.list')
            ->with('status', 'Event updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('events.list')
            ->with('status', 'Event deleted successfully');
    }
}
