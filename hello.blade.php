<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <link rel="stylesheet" href="">
</head>

<body>
    @env('local')
    // The application is in the local environment...
    @elseenv('testing')
    // The application is in the testing environment...
    @else
    // The application is not in the local or testing environment...
    @endenv
    @if($a == 1):
    123
    @endif
    {{ $a }}
</body>

</html>