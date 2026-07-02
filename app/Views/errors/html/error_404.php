<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found â€” SYNAPSE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #F9FAFB;
            font-family: 'Inter', sans-serif;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .icon {
            font-size: 5rem;
            color: var(--primary-500);
            margin-bottom: 1rem;
            background: -webkit-linear-gradient(135deg, var(--primary-400), var(--primary-600));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h1 {
            font-size: 4rem;
            font-weight: 800;
            margin: 0;
            color: #1F2937;
        }
        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 0.5rem;
            color: #374151;
        }
        p {
            color: #6B7280;
            margin-top: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: var(--primary-600);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: var(--primary-700);
        }
        .details {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #E5E7EB;
            font-size: 0.85rem;
            color: #9CA3AF;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <h1>404</h1>
        <h2>Signal Lost</h2>
        <p>The page you are looking for has been moved, deleted, or possibly never existed. Let's get you back to the SYNAPSE network.</p>
        
        <a href="/" class="btn"><i class="fas fa-home" style="margin-right: 0.5rem;"></i> Return to Dashboard</a>

        <?php if (ENVIRONMENT !== 'production') : ?>
            <div class="details">
                <strong>Debug Info:</strong><br>
                <?= nl2br(esc($message)) ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
