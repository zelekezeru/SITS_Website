@extends('layouts.app')

@section('main_content')
    <!--================Home Banner Area =================-->
    <section class="banner_area">
        <div class="banner_inner d-flex align-items-center">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="banner_content text-center">
                            <h2>Events</h2>
                            <div class="page_link">
                                <a href="{{url('/')}}">Home</a>
                                <a href="{{ route('events.index') }}">Events</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start Events Area =================-->
    <div class=" section_gap_top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title">
                        <h2 class="mb-3">Upcoming Events</h2>
                        <p>Join us at one of our exciting events. Don't miss out on the latest happenings!</p>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($events as $event)
                    <div class="col-lg-4 col-md-6">
                        <div class="single_event">
                            <div class="event_head">
                                @if ($event->banner)
                                    <img class="img-fluid" src="{{ asset('storage/' . $event->banner) }}" alt="{{ $event->title }}" />
                                @else
                                    <img class="img-fluid" src="{{ asset('images/default-banner.jpg') }}" alt="Default Banner" />
                                @endif
                            </div>
                            <div class="event_content mb-5">
                                <h4 class="mb-3 mt-4">
                                    <a href="{{ route('events.show', $event->id) }}">{{ $event->title }}</a>
                                </h4>
                                <p>{{ Str::limit($event->description, 100) }}</p>
                                <div class="event_meta d-flex justify-content-between align-items-center">
                                    <span class="date">{{ \Carbon\Carbon::parse($event->date)->format('F j, Y') }}</span>
                                    <span class="location">{{ $event->location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--================ End Events Area =================-->
@endsection
