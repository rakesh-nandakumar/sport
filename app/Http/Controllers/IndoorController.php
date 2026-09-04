<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Indoor;
use App\Models\Tournament;
use Illuminate\Http\Request;

class IndoorController extends Controller
{

    public function index(){
        return view('indoor.index', [
            'indoors'=>Indoor::latest()->filters(request(['activity','search']))->get(),
            'tournaments'=> Tournament::all()

        ]);
    }


// show individual Indoors
    public function show(Indoor $indoors){
        $gallery = explode('|', $indoors->gallery);

        $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        return view('indoor.show', [
            'indoors'=> $indoors,
            'gallery'=>$gallery,
            'days'=>$days

        ]);
    }

    public function ClientShow(Request $request, Indoor $indoors){
        $bookings = Booking::where('indoor_id', $indoors->id)
            ->when($request->filled('resource'), fn ($q) => $q->where('resource_id', $request->integer('resource')))
            ->with(['resource', 'resource.activity'])
            ->get();

        $stats = $this->statsBlock($indoors);

        $events = $this->bookingEvents($bookings);

        $place = Indoor::find($indoors->id);

        //find the indoor of the online user
        $indoors = Indoor::where('user_id', auth()->id())->get();

        return view('indoor.client-show', [
            'indoors'=> $indoors,
            'bookings'=>$bookings,
            'resources' => $place->resources()->with('activity')->get(),
            'place'=>$place,
            'events'=>$events,
            'bookingAnalysis'=> $stats['bookingAnalysis'],
            'months'=> $stats['months'],
            'monthCount'=> $stats['monthCount'],
            'totalBooking'=> $stats['totalBooking'],
            'totalBookingThisWeek'=> $stats['totalBookingThisWeek'],
            'totalBookingThisMonth'=> $stats['totalBookingThisMonth'],
            'totalBookingToday'=> $stats['totalBookingToday'],
        ]);

    }

    public function create(){
        return view('indoor.create', [
            'indoors'=>auth()->user()->indoors
         ]);
    }

    public function adminShow(Request $request, Indoor $indoors){
        $allIndoors = Indoor::all();

        $bookings = Booking::where('indoor_id', $indoors->id)
            ->when($request->filled('resource'), fn ($q) => $q->where('resource_id', $request->integer('resource')))
            ->with(['resource', 'resource.activity'])
            ->get();

        $stats = $this->statsBlock($indoors);

        $events = $this->bookingEvents($bookings);

        $place = Indoor::find($indoors->id);

        //find the indoor of the online user
        $indoors = Indoor::where('user_id', auth()->id())->get();

        return view('indoor.admin-show', [
            'indoors'=> $indoors,
            'bookings'=>$bookings,
            'resources' => $place->resources()->with('activity')->get(),
            'place'=>$place,
            'events'=>$events,
            'bookingAnalysis'=> $stats['bookingAnalysis'],
            'months'=> $stats['months'],
            'monthCount'=> $stats['monthCount'],
            'totalBooking'=> $stats['totalBooking'],
            'totalBookingThisWeek'=> $stats['totalBookingThisWeek'],
            'totalBookingThisMonth'=> $stats['totalBookingThisMonth'],
            'totalBookingToday'=> $stats['totalBookingToday'],
            'allIndoors'=>$allIndoors
        ]);


    }

    //show edit form
    public function edit(Indoor $indoors){
        return view('indoor.edit', ['indoors' => $indoors]);
    }


    public function store(Request $request){
        $formFields = $request->validate([
            'title'=> 'required',
            'tags'=>'required',
            'location' => 'required',
            'email' => 'required',
            'website' => 'required',
            'description' => 'required',
            'contact_number' => 'required',
            'price' => 'required',
            'monday_opening' => 'nullable',
            'monday_closing' => 'nullable',
            'tuesday_opening' => 'nullable',
            'tuesday_closing' => 'nullable',
            'wednesday_opening' => 'nullable',
            'wednesday_closing' => 'nullable',
            'thursday_opening' => 'nullable',
            'thursday_closing' => 'nullable',
            'friday_opening' => 'nullable',
            'friday_closing' => 'nullable',
            'saturday_opening' => 'nullable',
            'saturday_closing' => 'nullable',
            'sunday_opening' => 'nullable',
            'sunday_closing' => 'nullable',

        ]);

        if($request->hasFile('photo')){
            $formFields['photo'] = $request->file('photo')->store('photos','public');
        }



        $gallery = array();
        if($files = $request->file('gallery')){
            foreach($files as $file){
                $galley_name = md5(rand(1000,10000));
                $ext = strtolower($file->getClientOriginalExtension());
                $gallery_full_name = $galley_name.'.'.$ext;
                $upload_path = 'storage/photos/gallery/';
                $gallery_url = $upload_path.$gallery_full_name;
                $file->move($upload_path, $gallery_full_name );
                $gallery[] = $gallery_url;
            }
        }

        $formFields['gallery'] = implode('|', $gallery);




        $formFields['user_id'] = auth()->id();

        Indoor::create($formFields);

        return redirect('/')->with('message', 'Listing created successfully !');
     }

 //update indoor
     public function update(Request $request, Indoor $indoors){

        //make sure logged in user is owner

        if (auth()->user()->id !== $indoors->user_id){
            abort(403, 'Unauthorized action');
        }
        $formFields = $request->validate([
            'title'=> 'required',
            'tags'=>'required',
            'location' => 'required',
            'email' => 'required',
            'website' => 'required',
            'description' => 'required',
            'contact_number' => 'required',
            'price' => 'required',
            'monday_opening' => 'nullable',
            'monday_closing' => 'nullable',
            'tuesday_opening' => 'nullable',
            'tuesday_closing' => 'nullable',
            'wednesday_opening' => 'nullable',
            'wednesday_closing' => 'nullable',
            'thursday_opening' => 'nullable',
            'thursday_closing' => 'nullable',
            'friday_opening' => 'nullable',
            'friday_closing' => 'nullable',
            'saturday_opening' => 'nullable',
            'saturday_closing' => 'nullable',
            'sunday_opening' => 'nullable',
            'sunday_closing' => 'nullable',
        ]);

        if($request->hasFile('photo')){
            $formFields['photo'] = $request->file('photo')->store('photos','public');
        }

         $gallery = array();
         if($files = $request->file('gallery')){
             foreach($files as $file){
                 $galley_name = md5(rand(1000,10000));
                 $ext = strtolower($file->getClientOriginalExtension());
                 $gallery_full_name = $galley_name.'.'.$ext;
                 $upload_path = 'storage/photos/gallery/';
                 $gallery_url = $upload_path.$gallery_full_name;
                 $file->move($upload_path, $gallery_full_name );
                 $gallery[] = $gallery_url;
             }
         }

         $formFields['gallery'] = implode('|', $gallery);

        $formFields['user_id'] = auth()->id();

        $indoors->update($formFields);

        return back()->with('message', 'Listing updated successfully !');
     }

     //delete indoor\
        public function destroy(Indoor $indoors){
             //make sure logged in user is owner

        if (auth()->user()->id !== $indoors->user_id){
            abort(403, 'Unauthorized action');
        }
            $indoors->delete();
            return redirect('/')->with('message', 'Listing deleted successfully !');
        }

        //Manage indoors
        public function manage(){
            return view('indoor.manage', [
                'indoors'=>auth()->user()->indoors
             ]);
        }


        /** Shared KPI stats for the owner/admin dashboards. */
        private function statsBlock(Indoor $indoors): array
        {
            $totalBooking = Booking::where('indoor_id', $indoors->id)->count();

            $totalBookingThisWeek = Booking::where('indoor_id', $indoors->id)
                ->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()])->count();

            $totalBookingThisMonth = Booking::where('indoor_id', $indoors->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])->count();

            $totalBookingToday = Booking::where('indoor_id', $indoors->id)
                ->whereDate('start_time', now())->count();

            $bookingAnalysis = Booking::select('id', 'start_time')
                ->where('indoor_id', $indoors->id)
                ->get()
                ->groupBy(fn ($date) => $date->start_time->format('M'));

            $months = [];
            $monthCount = [];

            foreach ($bookingAnalysis as $month => $values){
                $months[] = $month;
                $monthCount[] = count($values);
            }

            return compact('totalBooking', 'totalBookingThisWeek', 'totalBookingThisMonth', 'totalBookingToday', 'bookingAnalysis', 'months', 'monthCount');
        }

        /** FullCalendar-friendly events for a given booking collection. */
        private function bookingEvents($bookings): array
        {
            $events = [];

            foreach ($bookings as $booking){
                $events[] = [
                    'title' => 'Booked',
                    'start' => $booking->start_time->format('Y-m-d H:i:s'),
                    'end' => $booking->finish_time->format('Y-m-d H:i:s'),
                ];
            }

            return $events;
        }

}
