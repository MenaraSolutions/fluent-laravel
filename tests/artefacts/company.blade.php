@section('content')
    <div class="card-panel">
        <h3>@lang("The company")</h3>

        <p>
            {{ __('Fluent is operated by Menara Solutions Pty Ltd, a Melbourne-based Australian company. It\'s great.') }}
        </p>

        <p>
            {{ trans("We run on caffeine and cool ideas. We like \"good\" music. I can use 'single' quotes here.") }}
        </p>
    </div>
@endsection