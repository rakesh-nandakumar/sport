@extends('layout')

@section('content')

<div class="form-shell">
    <div class="form-card form-card--wide">
        <div class="form-card__icon"><i class="fa-solid fa-trophy"></i></div>
        <header>
            <h2>Edit Tournament</h2>
            <p>Update your tournament details</p>
        </header>

        <form method="POST" action="/tournament/{{ $tournaments->id }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="title" class="field-label">Title</label>
                <input type="text" id="title" class="field-input" name="title" placeholder="Example: RCL tournament"
                    value="{{ $tournaments->title }}" />
                @error('title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="date" class="field-label">Date</label>
                <input type="date" id="date" class="field-input" name="tournamentDate" value="{{ $tournaments->tournamentDate }}" />
                @error('date')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="noOFplayers" class="field-label">No of players</label>
                <input type="text" id="noOFplayers" class="field-input" name="noOFplayers" value="{{ $tournaments->noOFplayers }}" />
                @error('noOFplayers')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="contact_number" class="field-label">Contact number</label>
                <input type="text" id="contact_number" class="field-input" name="contact_number" placeholder="Example: 011*******"
                    value="{{ $tournaments->contact_number }}" />
                @error('contact_number')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="entry_fee" class="field-label">Entry fee</label>
                <input type="text" id="entry_fee" class="field-input" name="entry_fee" placeholder="Example: Rs 500 per team"
                    value="{{ $tournaments->entry_fee }}" />
                @error('entry_fee')
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
                <label for="description" class="field-label">Description</label>
                <textarea id="description" class="field-input" name="description" rows="6"
                    placeholder="Include sports, indoor specialities, price, etc">{{ $tournaments->description }}</textarea>
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
</div>

@endsection
