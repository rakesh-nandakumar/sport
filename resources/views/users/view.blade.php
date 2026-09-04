@extends('dashboard-layout')
@section('content')

    <div class="container-fluid px-4">
        <h1 class="mt-4">Manage Your Users</h1>

        @if (session('message'))
            <div class="alert alert-success">{{ session('message')}}</div>
        @endif

        <table class="table table-bordered">
            <thead>
            <tr>
                <th>User Name</th>
                <th>Role</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>
                        <a href="{{ url('/user/'.$user->id.'/show') }}">{{ $user->name }}</a>
                    </td>
                    <td>
                        {{ ucwords(str_replace('_', ' ', Str::snake($user->role_id->name))) }}
                    </td>
                    <td>
                        <a href="{{ url('/admin/user/'.$user->id.'/edit') }}" class="btn btn-success">Edit</a>
                    </td>
                    <td>
                        <form action="{{ url('/user/'.$user->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@endsection
