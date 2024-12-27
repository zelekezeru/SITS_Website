<x-mail::message>
# Introduction

<p>Name: {{ $details['name'] }}</p>
<p>Email: {{ $details['email'] }}</p>
<p>Message: {{ $details['message'] }}</p>

<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
