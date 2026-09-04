<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Indoor;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{


    public function store(Request $request)
    {
        if (Auth::check()){

            $validator = Validator::make($request->all(), [
                'comments' => 'required|string'

            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }


            $indoor = Indoor::find($request->post_id);

            if ($indoor) {
                Comment::create([
                    'indoor_id' => $request->post_id,
                    'user_id' => Auth::id(),
                    'comment' => $request->comments
                ]);
                return redirect()->back()->with('message', 'Comment added');

            }else{
                return redirect()->back()->with('message', 'Indoor not found');
            }


        }
        else{

            return redirect()->back()->with('message', 'You need to login to comment');

        }

    }
}
