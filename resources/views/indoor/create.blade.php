@extends('user-dashboard-layout')

@section('content')

    <script src="https://cdn.tailwindcss.com"></script>

<div
class="bg-gray-50 border border-gray-200 p-10 rounded max-w-lg mx-auto mt-24"
>
<header class="text-center">
    <h2 class="text-2xl font-bold uppercase mb-1">
        Create a Indoor
    </h2>
    <p class="mb-4">Post a Indoor to find customers</p>
</header>

<form  method="POST" action="/home" enctype="multipart/form-data">
    @csrf


    <div class="mb-6">
        <label for="title" class="inline-block text-lg mb-2"
            >Title</label
        >
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="title"
            placeholder="Example: Etihad Indoor"
        />
        @error('title')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label
            for="location"
            class="inline-block text-lg mb-2"
            >Location</label
        >
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="location"
            placeholder="Example: Kotahena, Colombo-12, etc"
        />
        @error('location')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label for="email" class="inline-block text-lg mb-2"
            >Contact Email</label
        >
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="email"
        />

        @error('email')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label
            for="website"
            class="inline-block text-lg mb-2"
        >
            Website/Application URL
        </label>
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="website"
        />

        @error('website')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label for="tags" class="inline-block text-lg mb-2">
            Tags (Comma Separated)
        </label>
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="tags"
            placeholder="Example: Futsal, Cricket, Badminton"
        />

        @error('tags')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label for="contact_number" class="inline-block text-lg mb-2">
            Contact number
        </label>
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="contact_number"
            placeholder="Example: 011*******"
        />

        @error('contact_number')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>



    <div class="mb-6">
        <label for="contact_number" class="inline-block text-lg mb-2">
            Price per Hour
        </label>
        <input
            type="text"
            class="border border-gray-200 rounded p-2 w-full"
            name="price"
            placeholder="Example: Laravel, Backend, Postgres, etc"
        />

        @error('price')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <label for="photo" class="inline-block text-lg mb-2">
            Image
        </label>
        <input
            type="file"
            class="border border-gray-200 rounded p-2 w-full"
            name="photo"
        />

        @error('photo')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>


    <div>
        <label for="photo" class="inline-block text-lg mb-2">
            Gallery
        </label>

        <input
            type="file"
            class="border border-gray-200 rounded p-2 w-full"
            name="gallery[]"
            multiple
            id="multgaller"
        />

        @error('gallery[]')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror

    </div>

    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Opening and Closing Times</h3>
    
        @php
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        @endphp
    
        @foreach($days as $day)
            <div class="mb-4">
                <label class="block mb-2 capitalize">{{ ucfirst($day) }} Opening Time</label>
                <input
                    type="time"
                    name="{{ $day }}_opening"
                    class="border border-gray-200 rounded p-2 w-full"
                />
    
                @error("{$day}_opening")
                <p class="text-red-500 text-xs mt-1"> {{$message}}</p>
                @enderror
            </div>
    
            <div class="mb-4">
                <label class="block mb-2 capitalize">{{ ucfirst($day) }} Closing Time</label>
                <input
                    type="time"
                    name="{{ $day }}_closing"
                    class="border border-gray-200 rounded p-2 w-full"
                />
    
                @error("{$day}_closing")
                <p class="text-red-500 text-xs mt-1"> {{$message}}</p>
                @enderror
            </div>
        @endforeach
    </div>
    

    <div class="mb-6">
        <label
            for="description"
            class="inline-block text-lg mb-2"
        >
             Description
        </label>
        <textarea
            class="border border-gray-200 rounded p-2 w-full"
            name="description"
            rows="10"
            placeholder="Include sports, indoor sepacialities, price, etc"
        ></textarea>

        @error('description')
        <p class="text-red-500 text-xs mt-1"> {{$message}}</p>


        @enderror
    </div>

    <div class="mb-6">
        <button type="submit"
            class="bg-red-500 text-white rounded py-2 px-4 hover:bg-black">
            Create Indoor
        </button>

        <a href="/" class="text-black ml-4"> Back </a>
    </div>
</form>
</div>

@endsection
