<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e5a96">
    <title>You're offline — AllLeads CRM</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #001e5a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .card {
            background: #1e3a6a;
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            max-width: 28rem;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .5);
        }

        .icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem;
            color: #f0781e;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .75rem;
        }

        p {
            font-size: .9375rem;
            line-height: 1.6;
            color: #94a3b8;
        }

        .btn {
            display: inline-block;
            margin-top: 2rem;
            padding: .625rem 1.5rem;
            background: #1e5a96;
            color: #fff;
            border-radius: .5rem;
            font-size: .9375rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: background .15s;
        }

        .btn:hover {
            background: #1e7896;
        }
    </style>
</head>

<body>
    <div class="card">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 3l1.664 1.664M6.28 6.28A7.468 7.468 0 005.25 9.75c0 2.07.84 3.943 2.2 5.3M17.72 17.72A7.5 7.5 0 0018.75 9.75a7.468 7.468 0 00-1.03-3.47M12 12a3 3 0 00-1.664-2.7M3 3l18 18" />
        </svg>
        <h1>You're offline</h1>
        <p>AllLeads CRM requires an internet connection. Please check your network and try again.</p>
        <button class="btn" onclick="window.location.reload()">Try again</button>
    </div>
</body>

</html>
