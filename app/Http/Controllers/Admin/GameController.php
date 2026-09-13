<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        return view('admin.games.index', [
            'games' => Game::with('activityType')->withCount('services')->orderBy('activity_type_id')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Game);
    }

    public function store(Request $request): RedirectResponse
    {
        Game::create($this->validated($request));

        return redirect()->route('admin.games.index')->with('message', 'Game added.');
    }

    public function edit(Game $game): View
    {
        return $this->form($game);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $game->update($this->validated($request));

        return redirect()->route('admin.games.index')->with('message', 'Game updated.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return back()->with('message', 'Game deleted.');
    }

    protected function form(Game $game): View
    {
        return view('admin.games.form', [
            'game' => $game,
            'activityTypes' => ActivityType::where('requires_game', true)->orderBy('name')->get(),
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'name' => ['required', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:40'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:32'],
        ]);
    }
}
