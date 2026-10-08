@if(request()->boolean('native'))
@include('experience.native-backup.experience.exhibitions')
@else
@include('experience.reference')
@endif
