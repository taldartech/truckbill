@extends('layouts.app')

@section('title', 'Edit Email Template')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <div class="card-title">Edit Email Template</div>
                    </div>

                    <form action="{{ route('email_templates.update', $template) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            @include('email_templates.partials.form')
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">Update Template</button>
                            <a href="{{ route('email_templates.preview', $template) }}" class="btn btn-info">Preview</a>
                            <a href="{{ route('email_templates.index') }}" class="btn btn-brand-primary">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            function initEmailTemplateSelect2() {
                if (!window.jQuery || !$.fn || !$.fn.select2) {
                    return;
                }

                $('#company_id, #allowed_variables, #allowed_roles').each(function() {
                    const $element = $(this);
                    const placeholder = $element.data('placeholder') || 'Select options';

                    if ($element.hasClass('select2-hidden-accessible')) {
                        $element.select2('destroy');
                    }

                    $element.select2({
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: true,
                        closeOnSelect: false
                    });
                });
            }

            $(document).ready(initEmailTemplateSelect2);
            window.addEventListener('load', initEmailTemplateSelect2);
            setTimeout(initEmailTemplateSelect2, 150);
        })();
    </script>
@endsection
