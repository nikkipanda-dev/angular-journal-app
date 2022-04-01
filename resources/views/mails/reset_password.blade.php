@component('mail::message')
# Intro

Lorem ipsum

@component('mail::button', ['url' => 'https://youtube.com'])
Click me
@endcomponent

Thanks, <br />
{{ config('app.name') }}
@endcomponent