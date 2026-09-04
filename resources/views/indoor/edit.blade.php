@extends('layout')

@section('content')

<div class="form-shell">
  <div class="flex w-full flex-col items-center gap-8">
    <div class="form-card form-card--wide">
        <div class="form-card__icon"><i class="fa-solid fa-building"></i></div>
        <header>
            <h2>Edit Indoor Details</h2>
            <p>{{ $indoors->title }}</p>
        </header>

        <form method="POST" action="/home/{{ $indoors->id }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="title" class="field-label">Title</label>
                <input type="text" id="title" class="field-input" name="title"
                    placeholder="Example: Etihad Indoor" value="{{ $indoors->title }}" />
                @error('title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="location" class="field-label">Location</label>
                <input type="text" id="location" class="field-input" name="location"
                    placeholder="Example: Kotahena, Colombo-12, etc" value="{{ $indoors->location }}" />
                @error('location')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="email" class="field-label">Contact Email</label>
                <input type="text" id="email" class="field-input" name="email" value="{{ $indoors->email }}" />
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="website" class="field-label">Website/Application URL</label>
                <input type="text" id="website" class="field-input" name="website" value="{{ $indoors->website }}" />
                @error('website')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="tags" class="field-label">Tags (Comma Separated)</label>
                <input type="text" id="tags" class="field-input" name="tags"
                    placeholder="Example: Futsal, Cricket, Badminton" value="{{ $indoors->tags }}" />
                @error('tags')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="contact_number" class="field-label">Contact number</label>
                <input type="text" id="contact_number" class="field-input" name="contact_number"
                    placeholder="Example: 011*******" value="{{ $indoors->contact_number }}" />
                @error('contact_number')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="price" class="field-label">Price per Hour</label>
                <input type="text" id="price" class="field-input" name="price" value="{{ $indoors->price }}" />
                @error('price')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="photo" class="field-label">Image</label>
                <input type="file" id="photo" class="field-input" name="photo" />
                @error('photo')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="multgaller" class="field-label">Gallery</label>
                <input type="file" id="multgaller" class="field-input" name="gallery[]" multiple />
                @error('gallery[]')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <h3 class="field-label mb-3">Opening &amp; Closing Times</h3>

                @php
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-100">
                    @foreach($days as $day)
                        <div class="grid grid-cols-3 items-center gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 odd:bg-gray-50/70">
                            <span class="text-sm font-medium capitalize text-gray-700">{{ $day }}</span>
                            <div>
                                <input type="time" name="{{ $day }}_opening" value="{{ $indoors->{$day . '_opening'} }}"
                                    class="field-input" />
                                @error("{$day}_opening")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input type="time" name="{{ $day }}_closing" value="{{ $indoors->{$day . '_closing'} }}"
                                    class="field-input" />
                                @error("{$day}_closing")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="field">
                <label for="description" class="field-label">Description</label>
                <textarea id="description" class="field-input" name="description" rows="8"
                    placeholder="Include sports, indoor specialties, price, etc">{{ $indoors->description }}</textarea>
                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/" class="text-sm font-medium text-gray-500 hover:text-gray-800">Back</a>
            </div>
        </form>
    </div>

    <div class="form-card form-card--wide">
        @livewire('manage-resources', ['indoor' => $indoors])
    </div>
  </div>
</div>

@endsection
