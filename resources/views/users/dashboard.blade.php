@extends('layout')
@section('content')

<div class="bg-gray-50 border border-gray-200 pl-10 pr-10 pb- 20 rounded mt-40">
    <header>
        <h1
            class="text-3xl text-center font-bold my-6 uppercase"
        >
            Manage Your Users
        </h1>
    </header>

    <table class="w-full table-auto rounded-sm">
        <tbody>
            
            @foreach ($users as $user)
                
           
            <tr class="border-gray-300">
                <td
                    class="px-4 py-8 border-t border-b border-gray-300 text-lg"
                >
                    <a href="show.html">
                        {{$user->name}}
                    </a>
                </td>

              
                    <td
                        class="px-4 py-8 border-t border-b border-gray-300 text-lg"
                    >
                        <a href="show.html">
                            {{ucwords(Str_replace('_',' ', Str::snake($user->role_id->name)))}}
                        </a>
                    </td>
                <td
                    class="px-4 py-8 border-t border-b border-gray-300 text-lg"
                >
                    <a
                        href="/user/{{$user->id}}/edit"
                        class="text-blue-400 px-6 py-2 rounded-xl"
                        ><i
                            class="fa-solid fa-pen-to-square"
                        ></i>
                        Edit</a
                    >
                </td>
                <td
                    class="px-4 py-8 border-t border-b border-gray-300 text-lg"
                >
                <form action="/user/{{$user->id}}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-500"><i
                        class="fa-solid fa-trash-can"
                    ></i>
                    Delete</button>
                </form>
                  </section>
                </td>
            </tr>

            @endforeach
           
           

        </tbody>
    </table>
</div>

@endsection