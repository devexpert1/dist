<html>
<head><title>Ask Question</title></head>
<body>
Hi {{ $data['to_name'] }},
<br><br>
{{ $data['from_name'] }} asked a question about your trip "{{ $data['trip_title'] }}" in {{ $data['trip_destination'] }}.
<br><br>
"{{ $data['question'] }}"
<br><br>
Click <a href="mailto:{{ $data['from_email'] }}?Subject=Reply%20-%20Questions%20asked%20regarding%20a%20trip"> here </a> to answer
<br><br>
WorldVybe Team
</body>
</html>