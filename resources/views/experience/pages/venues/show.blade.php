@if(request()->boolean('native'))
@include('experience.native-backup.experience.pages.venues.show')
@else
@include('experience.reference')
@endif
