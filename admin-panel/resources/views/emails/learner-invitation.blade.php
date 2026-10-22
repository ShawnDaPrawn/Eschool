<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school_name }}</title>
</head>
<body>
    {{-- Rendered by EmailTemplateService::render(), which escapes every value. --}}
    {!! $body !!}
</body>
</html>
