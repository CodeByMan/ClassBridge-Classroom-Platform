@if ($message = Session::get('success'))
    <div id="flash-success" class="gc-alert gc-alert--success" role="status"><i class="fa-solid fa-circle-check"></i><span class="flex-1">{{ $message }}</span><button type="button" class="gc-icon-button" data-dismiss-target="#flash-success" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button></div>
@endif
@if ($message = Session::get('error'))
    <div id="flash-error" class="gc-alert gc-alert--error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span class="flex-1">{{ $message }}</span><button type="button" class="gc-icon-button" data-dismiss-target="#flash-error" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button></div>
@endif
@if ($message = Session::get('warning'))
    <div id="flash-warning" class="gc-alert gc-alert--warning" role="alert"><i class="fa-solid fa-triangle-exclamation"></i><span class="flex-1">{{ $message }}</span><button type="button" class="gc-icon-button" data-dismiss-target="#flash-warning" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button></div>
@endif
@if ($errors->any())
    <div id="flash-validation" class="gc-alert gc-alert--error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><div class="flex-1"><strong>Please correct the following:</strong><ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button type="button" class="gc-icon-button" data-dismiss-target="#flash-validation" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button></div>
@endif
