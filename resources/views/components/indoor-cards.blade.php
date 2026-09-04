@props(['item'])

<div class="indoor-items">
    
            
    <div class="indoor-photo">
        <img src="images/CR7.png">

    </div>
    <div class="indoor-details">
        <h1><a href="/home/{{$item['id']}}">{{$item['title']}}</a></h1>

        <p>{{$item['description']}}</p>
        <p> <i class="fa-solid fa-location-dot"></i> {{$item['location']}} </p>
       
        <x-listing-tags :tagsCsv="$item->tags"/>
    </div>
</div>