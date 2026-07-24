<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'KICC 3D Viewer')</title>
    <meta name="description" content="@yield('description', 'KICC National Exhibition - 3D Experience')">
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>