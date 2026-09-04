<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Newsletter\Facades\Newsletter;



class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $mailchimp = new \MailchimpMarketing\ApiClient();

        $mailchimp->setConfig([
            'apiKey' => config('services.mailchimp.key'),
            'server' => 'us22'

        ]);

        $response = $mailchimp->lists->addListMember(config('services.mailchimp.lists.subscribers'),[
            'email_address' => request('email'),
            'status' => 'subscribed'
        ]);



        return back()->with('message', 'Subscribed successfully');
    }
}
