<?php
// CONFIGURATION
$participants = [
    "Kyra" => "1234",
    "Kipkip" => "2345",
    "GreeYah" => "3456",
    "PaCards" => "4567",
    "Roed" => "5678",
    "Shingshing" => "6789",
    "Ronnie" => "7890",
    "Toshierooh" => "8901",
    "Ai La" => "9012",
    "AnnLoveBernd" => "0123"
];

$data_dir = __DIR__ . "/data";
$mapping_file = $data_dir . "/mapping.json";
$wishlist_file = $data_dir . "/wishlist.json";

// Ensure data folder
if (!is_dir($data_dir)) mkdir($data_dir, 0777, true);

// Load data
$mapping = file_exists($mapping_file) ? json_decode(file_get_contents($mapping_file), true) : [];
$wishlist = file_exists($wishlist_file) ? json_decode(file_get_contents($wishlist_file), true) : [];

// SESSION for login
session_start();

// Login handling
if (isset($_POST['pin'])) {
    $pin = trim($_POST['pin']);
    $user = array_search($pin, $participants);
    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        $login_error = "Invalid PIN";
    }
}

// Ensure user is logged in
$user = $_SESSION['user'] ?? null;

// DRAW handling
if ($user && isset($_POST['action']) && $_POST['action'] === 'draw') {
    if (!isset($mapping[$user])) {
        $taken = array_values($mapping);
        $possible = array_filter(array_keys($participants), function($p) use ($user, $taken) {
            return $p !== $user && !in_array($p, $taken);
        });
        if (count($possible) > 0) {
            $receiver = $possible[array_rand($possible)];
            $mapping[$user] = $receiver;
            file_put_contents($mapping_file, json_encode($mapping));
        } else {
            $draw_error = "No recipients left!";
        }
    }
}

// Wishlist handling
if ($user && isset($_POST['wishlist_item'])) {
    $item = trim($_POST['wishlist_item']);
    if ($item !== '') {
        $wishlist[$user][] = $item;
        file_put_contents($wishlist_file, json_encode($wishlist));
    }
}

// Get current user's receiver
$receiver = $mapping[$user] ?? null;
$receiver_wishlist = $receiver ? ($wishlist[$receiver] ?? []) : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secret Santa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f2f5f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        h3 {
            color: #444;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px 5px 0 0;
            font-size: 16px;
            color: #fff;
            background: #ff6b6b;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #ff4757; }
        .btn:disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        input[type="text"], input[type="password"] {
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 70%;
            margin-bottom: 10px;
        }
        #wishlist-list, ul { 
            list-style-type: disc; 
            padding-left: 20px; 
            text-align: left;
        }
        .card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .wishlist-title {
            color: #555;
            margin-bottom: 5px;
        }
        .logout {
            margin-top: 20px;
            background: #576574;
        }
        .logout:hover {
            background: #2f3542;
        }
        p { color: #333; }
    </style>
</head>
<body>

<div class="container">
<?php if (!$user): ?>
    <h2>🎄 Enter Your PIN to Login</h2>
    <?php if (!empty($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>
    <form method="POST">
        <input type="password" name="pin" placeholder="Your PIN" required>
        <button type="submit" class="btn">Login</button>
    </form>
<?php else: ?>
    <h2>Welcome, <?= htmlspecialchars($user) ?> </h2>

    <div class="card">
        <h3>SPY BUNOT BUNOT</h3>
        <?php if ($receiver): ?>
            <p>Imong nabunotan kay si: <strong><?= htmlspecialchars($receiver) ?></strong></p>
            <h4 class="wishlist-title">Iyang Wishlist:</h4>
            <?php if (count($receiver_wishlist) > 0): ?>
                <ul>
                    <?php foreach ($receiver_wishlist as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>(No wishlist yet)</p>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="draw">
                <button type="submit" class="btn">Draw My Recipient</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Your Wishlist</h3>
        <form method="POST">
            <input type="text" name="wishlist_item" placeholder="Add item to your wishlist" required>
            <button type="submit" class="btn">Add</button>
        </form>
        <?php if (!empty($wishlist[$user])): ?>
            <ul id="wishlist-list">
                <?php foreach ($wishlist[$user] as $item): ?>
                    <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <form method="POST">
        <button name="logout" value="1" class="btn logout">Logout</button>
    </form>

    <?php
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    ?>
<?php endif; ?>
</div>

</body>
</html>
