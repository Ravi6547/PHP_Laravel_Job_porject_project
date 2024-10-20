<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Job Notification Email</title>
</head>
<body>
    <h1>hello{{ $emaildata['employer']->name }}</h1>
    <p>job title {{ $emaildata['job']->title }}</p>

    <p>Employe details:</p> 
    <p> Name:{{ $emaildata['user']->name }}</p>
    <p> Email:{{ $emaildata['user']->email }}</p>
    <p> Mobile:{{ $emaildata['user']->mobile }}</p>
</body>
</html>