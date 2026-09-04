<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class tournamentController extends Controller
{

    public function show(Tournament $tournaments)
    {
        // $tournaments already contains the instance of Tournament due to Route-Model Binding
        return view('tournament.show', [
            'tournament' => $tournaments,
        ]);
    }
    public function create()
    {
        return view('tournament.create');

    }

    public function manage()
    {
        return view('tournament.manage',
            ['tournaments' => auth()->user()->tournaments,
                'indoors'=>auth()->user()->indoors]);
    }

    public function edit(Tournament $tournaments)
    {
        return view('tournament.edit', ['tournaments' => $tournaments]);
    }

    public function store(Request $request)
    {



        $formFields = $request->validate([
            'title' => 'required',
            'tournamentDate' => 'required',
            'noOFplayers' => 'required',
            'contact_number' => 'required',
            'entry_fee' => 'required',
            'description' => 'required',
        ]);

        if ($request->hasFile('photo')) {
            $formFields['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $formFields['location'] = "default";

        $formFields['user_id'] = auth()->id();
        Tournament::create($formFields);

        return redirect('/')->with('message', 'Tournament created successfully!');
    }

public function update(Request $request, Tournament $tournaments)
{

    if (auth()->user()->id !== $tournaments->user_id){
        abort(403, 'Unauthorized action');
    }
    $formFields = $request->validate([
        'title' => 'required',
        'tournamentDate' => 'required',
        'noOFplayers' => 'required',
        'contact_number' => 'required',
        'entry_fee' => 'required',
        'description' => 'required',
    ]);

    if ($request->hasFile('photo')) {
        $formFields['photo'] = $request->file('photo')->store('photos', 'public');
    }


    $formFields['user_id'] = auth()->id();
    $tournaments->update($formFields);

    return redirect('/')->with('message', 'Tournament Updated successfully!');
}

    public function destroy(Tournament $tournament)
    {
        $tournament->delete();
        return redirect('/')->with('message', 'Tournament Deleted successfully!');
    }


}
