@foreach($templates as $model => $modelTemplates)
    {{ $model }} <br />
    @foreach($modelTemplates as $template)
        &nbsp;&nbsp;&nbsp;&nbsp;{{ $template->name }} - {{ $template->type }} <br />
    @endforeach
@endforeach