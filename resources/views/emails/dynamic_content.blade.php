@extends('emails.layouts.master')

@section('title', 'Notification')

@section('content')
    <tr>
        <td style="padding:24px;">
            {!! $bodyHtml !!}
        </td>
    </tr>
@endsection
