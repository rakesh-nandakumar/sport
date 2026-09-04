<?php

namespace App\Livewire;

use App\Models\Indoor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;

class PostComments extends Component
{
    public Indoor $indoor;
    #[Rule('required|min2|max:255')]
    public string $comment;

    public function mount(Indoor $indoor)
    {
        $this->indoor = $indoor;
    }

    public function work(){
        dd('working');
    }



    public function postComment()
    {

        dd('postComment method called');
        $this->validate([
            'comment' => ['required', 'min:2', 'max:255'],
        ]);
        $this->indoor->comments()->create([
            'comment' => $this->comment,
            'user_id' => auth()->id()
        ]);
        $this->reset('comment');
    }



    #[Computed()]
    public  function comments()
    {
        return $this?->indoor?->comments()->latest();

    }


    public function render()
    {
        return view('livewire.post-comments');
    }
}
