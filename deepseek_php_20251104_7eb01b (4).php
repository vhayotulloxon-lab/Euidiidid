<?php
define('API_TOKEN', '8493246716:AAEWDRGqKaZZORlYryL8W7qBTTRFT6F9OgU');
define('BOT_USERNAME', 'RekchiAi_bot');
define('ADMINS', ['Behruzxan', 'H08_09']);
define('RECEIPT_CHANNEL', '-1003181756108');
define('PROOF_CHANNEL', '-1003160041565'); 
define('PROMO_CHANNEL', '@insta_rekchi');
define('TOPUP_CARD_NUMBER', '9860606740391457');
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

define('NEOSMM_API_URL', 'https://neosmm.uz/api/v2');
define('NEOSMM_API_KEY', '67fbc8376cf2567fbc8376cf37');
define('NEOSMM_VIEWS_SERVICE_ID', '783');

define('DIAMOND_PRICES', [
    50 => 5000,
    100 => 9000,
    300 => 25000
]);

define('COIN_PRICES', [
    50 => 6000,
    100 => 11000,
    500 => 47000,
    1000 => 79000
]);

define('LIKES_SERVICE_ID', '1288');
define('SHIPMENTS_SERVICE_ID', '609');

define('VIEWS_PRICES', [
    1000 => 10,
    5000 => 45,
    10000 => 90,
    20000 => 170,
    50000 => 400,
    100000 => 750
]);

define('DATA_DIR', 'data');
define('BUTTONS_FILE', DATA_DIR . '/buttons.json');
define('VIDEOS_META', DATA_DIR . '/videos.json');
define('CONFIG_FILE', DATA_DIR . '/config.json');
define('PROMOCODES_FILE', DATA_DIR . '/promocodes.json');
define('ORDERS_FILE', DATA_DIR . '/orders.json');
define('STATES_FILE', DATA_DIR . '/states.json');
define('STICKERS_FILE', DATA_DIR . '/stickers.json');
define('CHANNEL_ADS_FILE', DATA_DIR . '/channel_ads.json');
define('PENDING_ORDERS_FILE', DATA_DIR . '/pending_orders.json');
define('PENDING_CHANNEL_ORDERS_FILE', DATA_DIR . '/pending_channel_orders.json');
define('PENALTY_CHECK_FILE', DATA_DIR . '/last_penalty_check.txt');
define('PROMO_MESSAGES_FILE', DATA_DIR . '/promo_messages.json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
if (!file_exists(DATA_DIR . '/videos')) mkdir(DATA_DIR . '/videos', 0777, true);
define('DB_HOST', 'localhost');
define('DB_USER', '68ed1ab05ca10_rekchiai');
define('DB_PASS', 'hayotulloxonv2008$');
define('DB_NAME', '68ed1ab05ca10_rekchiai');
define('DB_CHARSET', 'utf8mb4');
define('DB_CHARSET', 'utf8mb4');
// Qo'shilgan: atomik balans va yordamchi funksiyalar
require_once __DIR__ . '/db_helpers.php';
//validate_balances();
define('USER_CACHE_TTL', 30); // 5 daqiqa
define('CACHE_DIR', 'cache');
// CACHE FUNKSIYALARI
function get_cache($key) {
    $file = CACHE_DIR . '/' . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    
    $data = unserialize(file_get_contents($file));
    if (time() - $data['timestamp'] > USER_CACHE_TTL) {
        unlink($file);
        return null;
    }
    return $data['value'];
}

function set_cache($key, $value) {
    $file = CACHE_DIR . '/' . md5($key) . '.cache';
    $data = [
        'timestamp' => time(),
        'value' => $value
    ];
    file_put_contents($file, serialize($data));
}

function clear_cache($key = null) {
    if ($key) {
        $file = CACHE_DIR . '/' . md5($key) . '.cache';
        if (file_exists($file)) unlink($file);
    } else {
        $files = glob(CACHE_DIR . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
// MySQL connection function
// MySQL connection function
// MySQL connection function - OPTIMIZED VERSION
function get_db_connection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // Persistent connections
                PDO::ATTR_TIMEOUT => 3,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_COMPRESS => true,
            ]);
            
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    return $connection;
}
// Create users table
function create_users_table() {
    $db = get_db_connection();
    if (!$db) return false;
    
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(50) PRIMARY KEY,
        username VARCHAR(255) NULL,
        diamonds INT DEFAULT 0,
        coins INT DEFAULT 0,
        refs JSON,
        confirmed BOOLEAN DEFAULT FALSE,
        hashtags JSON,
        await_video BOOLEAN DEFAULT FALSE,
        views INT DEFAULT 0,
        intro_video_sent BOOLEAN DEFAULT FALSE,
        bonus_refs INT DEFAULT 0,
        ref_of VARCHAR(50) NULL,
        ref_reward_given BOOLEAN DEFAULT FALSE,
        video_watched BOOLEAN DEFAULT FALSE,
        used_diamonds INT DEFAULT 0,
        confirmed_refs JSON,
        ref_initial_notified BOOLEAN DEFAULT FALSE,
        ref_confirm_notified BOOLEAN DEFAULT FALSE,
        channel_rewards JSON,
        penalty_warnings INT DEFAULT 0,
        hashtag_warnings INT DEFAULT 0,
        like_errors INT DEFAULT 0,
        follower_errors INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_ref_of (ref_of),
        INDEX idx_confirmed (confirmed)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Create users table error: " . $e->getMessage());
        return false;
    }
}

// Load all users from MySQL - YANGI VERSIYA
function load_users() {
    $db = get_db_connection();
    if (!$db) {
        error_log("MySQL ulanmadi, foydalanuvchilar yuklanmadi");
        return [];
    }
    
    try {
        $stmt = $db->query("SELECT * FROM users");
        $users = [];
        while ($row = $stmt->fetch()) {
            // JSON fieldlarni decode qilish
            $row['refs'] = $row['refs'] ? json_decode($row['refs'], true) : [];
            $row['hashtags'] = $row['hashtags'] ? json_decode($row['hashtags'], true) : [];
            $row['confirmed_refs'] = $row['confirmed_refs'] ? json_decode($row['confirmed_refs'], true) : [];
            $row['channel_rewards'] = $row['channel_rewards'] ? json_decode($row['channel_rewards'], true) : [];
            
            $users[$row['id']] = $row;
        }
        error_log("MySQL dan " . db_get_users_count() . " ta foydalanuvchi yuklandi");
        return $users;
    } catch (PDOException $e) {
        error_log("Foydalanuvchilarni yuklashda xatolik: " . $e->getMessage());
        return [];
    }
}

// Save user to MySQL - YANGI VERSIYA
function save_user($user_id, $user_data) {
    $db = get_db_connection();
    if (!$db) {
        error_log("MySQL ulanmadi, foydalanuvchi saqlanmadi: " . $user_id);
        return false;
    }
    
    // JSON fieldlarni tayyorlash
    $refs_json = isset($user_data['refs']) ? json_encode($user_data['refs'], JSON_UNESCAPED_UNICODE) : '[]';
    $hashtags_json = isset($user_data['hashtags']) ? json_encode($user_data['hashtags'], JSON_UNESCAPED_UNICODE) : '[]';
    $confirmed_refs_json = isset($user_data['confirmed_refs']) ? json_encode($user_data['confirmed_refs'], JSON_UNESCAPED_UNICODE) : '[]';
    $channel_rewards_json = isset($user_data['channel_rewards']) ? json_encode($user_data['channel_rewards'], JSON_UNESCAPED_UNICODE) : '[]';
    
    try {
        // Foydalanuvchi mavjudligini tekshirish
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // UPDATE qilish
            $sql = "UPDATE users SET 
                    username = ?, diamonds = ?, coins = ?, refs = ?, confirmed = ?, 
                    hashtags = ?, await_video = ?, views = ?, intro_video_sent = ?, bonus_refs = ?, 
                    ref_of = ?, ref_reward_given = ?, video_watched = ?, used_diamonds = ?, 
                    confirmed_refs = ?, ref_initial_notified = ?, ref_confirm_notified = ?, 
                    channel_rewards = ?, penalty_warnings = ?, hashtag_warnings = ?, 
                    like_errors = ?, follower_errors = ? 
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $user_data['username'] ?? null,
                $user_data['diamonds'] ?? 0,
                $user_data['coins'] ?? 0,
                $refs_json,
                $user_data['confirmed'] ?? false,
                $hashtags_json,
                $user_data['await_video'] ?? false,
                $user_data['views'] ?? 0,
                $user_data['intro_video_sent'] ?? false,
                $user_data['bonus_refs'] ?? 0,
                $user_data['ref_of'] ?? null,
                $user_data['ref_reward_given'] ?? false,
                $user_data['video_watched'] ?? false,
                $user_data['used_diamonds'] ?? 0,
                $confirmed_refs_json,
                $user_data['ref_initial_notified'] ?? false,
                $user_data['ref_confirm_notified'] ?? false,
                $channel_rewards_json,
                $user_data['penalty_warnings'] ?? 0,
                $user_data['hashtag_warnings'] ?? 0,
                $user_data['like_errors'] ?? 0,
                $user_data['follower_errors'] ?? 0,
                $user_id
            ]);
        } else {
            // INSERT qilish
            $sql = "INSERT INTO users (
                    id, username, diamonds, coins, refs, confirmed, 
                    hashtags, await_video, views, intro_video_sent, bonus_refs, ref_of, 
                    ref_reward_given, video_watched, used_diamonds, confirmed_refs, 
                    ref_initial_notified, ref_confirm_notified, channel_rewards, 
                    penalty_warnings, hashtag_warnings, like_errors, follower_errors
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $user_id,
                $user_data['username'] ?? null,
                $user_data['diamonds'] ?? 0,
                $user_data['coins'] ?? 0,
                $refs_json,
                $user_data['confirmed'] ?? false,
                $hashtags_json,
                $user_data['await_video'] ?? false,
                $user_data['views'] ?? 0,
                $user_data['intro_video_sent'] ?? false,
                $user_data['bonus_refs'] ?? 0,
                $user_data['ref_of'] ?? null,
                $user_data['ref_reward_given'] ?? false,
                $user_data['video_watched'] ?? false,
                $user_data['used_diamonds'] ?? 0,
                $confirmed_refs_json,
                $user_data['ref_initial_notified'] ?? false,
                $user_data['ref_confirm_notified'] ?? false,
                $channel_rewards_json,
                $user_data['penalty_warnings'] ?? 0,
                $user_data['hashtag_warnings'] ?? 0,
                $user_data['like_errors'] ?? 0,
                $user_data['follower_errors'] ?? 0
            ]);
        }
        
        if ($result) {
            error_log("MySQL ga foydalanuvchi saqlandi: " . $user_id);
            return true;
        } else {
            error_log("MySQL ga foydalanuvchi saqlashda xatolik: " . $user_id);
            return false;
        }
        
    } catch (PDOException $e) {
        error_log("MySQL xatosi: " . $e->getMessage());
        return false;
    }
}
// eski update_user_balance ni shu bilan almashtiring
// update_user_balance funksiyasini quyidagicha yangilang:
function update_user_balance($user_id, $type, $amount, $operation = 'add') {
    $uid = strval($user_id);
    if (!in_array($type, ['diamonds', 'coins'])) {
        error_log("update_user_balance: invalid type {$type}");
        return false;
    }

    // Avval foydalanuvchi ma'lumotlarini yangilab olish
    refresh_user_data($uid);
    
    global $users;
    
    // Agar foydalanuvchi global massivda mavjud bo'lmasa, DB dan yuklash
    if (!isset($users[$uid])) {
        $users[$uid] = get_user($uid);
        if (!$users[$uid]) {
            error_log("update_user_balance: user not found {$uid}");
            return false;
        }
    }

    // Joriy balansni olish
    $current_balance = isset($users[$uid][$type]) ? intval($users[$uid][$type]) : 0;
    
    if ($operation === 'add') {
        $new_balance = $current_balance + intval($amount);
    } elseif ($operation === 'subtract') {
        $new_balance = max(0, $current_balance - abs(intval($amount)));
    } elseif ($operation === 'set') {
        $new_balance = max(0, intval($amount));
    } else {
        error_log("update_user_balance: unknown operation {$operation}");
        return false;
    }

    // MySQL ga yangilash
    $db = get_db_connection();
    if (!$db) {
        error_log("update_user_balance: DB connection failed");
        return false;
    }

    try {
        $stmt = $db->prepare("UPDATE users SET {$type} = ? WHERE id = ?");
        $result = $stmt->execute([$new_balance, $uid]);
        
        if ($result) {
            // Global massivni yangilash
            $users[$uid][$type] = $new_balance;
            error_log("Balans yangilandi: {$uid} - {$type}: {$current_balance} -> {$new_balance} ({$operation})");
            return $new_balance;
        } else {
            error_log("update_user_balance: UPDATE failed for {$uid}");
            return false;
        }
    } catch (PDOException $e) {
        error_log("update_user_balance DB error: " . $e->getMessage());
        return false;
    }
}

function get_user_balance($user_id, $type) {
    global $users;
    $uid = strval($user_id);
    
    // Avval yangilash
    refresh_user_data($uid);
    
    if (!isset($users[$uid])) {
        return 0;
    }
    
    return isset($users[$uid][$type]) ? intval($users[$uid][$type]) : 0;
}

function has_sufficient_balance($user_id, $type, $amount) {
    $balance = get_user_balance($user_id, $type);
    return $balance >= $amount;
}

// Get single user from MySQL - YANGI VERSIYA
function get_user($user_id) {
    $db = get_db_connection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $row['refs'] = $row['refs'] ? json_decode($row['refs'], true) : [];
            $row['hashtags'] = $row['hashtags'] ? json_decode($row['hashtags'], true) : [];
            $row['confirmed_refs'] = $row['confirmed_refs'] ? json_decode($row['confirmed_refs'], true) : [];
            $row['channel_rewards'] = $row['channel_rewards'] ? json_decode($row['channel_rewards'], true) : [];
            return $row;
        }
        return null;
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}
function get_ref_counts($user_id) {
    // $user may be in-memory or load from DB
    global $users;
    $uid = strval($user_id);

    if (isset($users[$uid]) && is_array($users[$uid])) {
        $user = $users[$uid];
    } else {
        $user = get_user($uid);
        if (!$user) {
            return ['confirmed' => 0, 'total' => 0, 'confirmed_with_bonus' => 0];
        }
    }

    // confirmed_refs safe parse
    $confirmed = 0;
    if (isset($user['confirmed_refs'])) {
        if (is_array($user['confirmed_refs'])) {
            $confirmed = count($user['confirmed_refs']);
        } elseif (!empty($user['confirmed_refs'])) {
            $tmp = json_decode($user['confirmed_refs'], true);
            $confirmed = is_array($tmp) ? count($tmp) : 0;
        }
    }

    // total refs safe parse
    $total = 0;
    if (isset($user['refs'])) {
        if (is_array($user['refs'])) {
            $total = count($user['refs']);
        } elseif (!empty($user['refs'])) {
            $tmp = json_decode($user['refs'], true);
            $total = is_array($tmp) ? count($tmp) : 0;
        }
    }

    // bonus_refs if exists
    $bonus = isset($user['bonus_refs']) ? intval($user['bonus_refs']) : 0;

    return [
        'confirmed' => $confirmed,
        'total' => $total,
        'confirmed_with_bonus' => $confirmed + $bonus
    ];
}
function persist_user($user_id) {
    global $users;
    $uid = strval($user_id);
    if (!isset($users[$uid]) || !is_array($users[$uid])) {
        error_log("persist_user: user not found in memory: {$uid}");
        return false;
    }
    $save_ok = save_user($uid, $users[$uid]);
    if (!$save_ok) {
        error_log("persist_user: save_user FAILED for {$uid}");
        return false;
    }
    // Refresh cached DB copy optionally
    $fresh = get_user($uid);
    if ($fresh) $users[$uid] = $fresh;
    error_log("persist_user: saved and refreshed user {$uid}");
    return true;
}
// DB statistik yordamchilari — joylashtiring get_db_connection() va get_user() funksiyalaridan keyin
function db_get_users_count() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) AS c FROM users");
        $row = $stmt->fetch();
        return intval($row['c'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_get_users_count error: " . $e->getMessage());
        return 0;
    }
}
// --- Qo'shilsin: DB statistik yordamchilari (joylashtiring db_get_users_count() dan keyin) ---
function db_sum_column($col) {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(`$col`),0) AS s FROM users");
        $row = $stmt->fetch();
        return intval($row['s'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_sum_column error: " . $e->getMessage());
        return 0;
    }
}

function db_count_confirmed_users() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) AS c FROM users WHERE confirmed = 1");
        $row = $stmt->fetch();
        return intval($row['c'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_count_confirmed_users error: " . $e->getMessage());
        return 0;
    }
}

function db_count_users_with_ref_of() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        // ref_of may be NULL or empty string
        $stmt = $db->query("SELECT COUNT(*) AS c FROM users WHERE ref_of IS NOT NULL AND ref_of <> ''");
        $row = $stmt->fetch();
        return intval($row['c'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_count_users_with_ref_of error: " . $e->getMessage());
        return 0;
    }
}

function db_count_referrers() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        // users who have at least one element in refs JSON
        $stmt = $db->query("SELECT COUNT(*) AS c FROM users WHERE COALESCE(JSON_LENGTH(refs),0) > 0");
        $row = $stmt->fetch();
        return intval($row['c'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_count_referrers error: " . $e->getMessage());
        return 0;
    }
}

function db_sum_confirmed_refs() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(COALESCE(JSON_LENGTH(confirmed_refs),0)),0) AS s FROM users");
        $row = $stmt->fetch();
        return intval($row['s'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_sum_confirmed_refs error: " . $e->getMessage());
        return 0;
    }
}

function db_sum_total_refs() {
    $db = get_db_connection();
    if (!$db) return 0;
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(COALESCE(JSON_LENGTH(refs),0)),0) AS s FROM users");
        $row = $stmt->fetch();
        return intval($row['s'] ?? 0);
    } catch (PDOException $e) {
        error_log("db_sum_total_refs error: " . $e->getMessage());
        return 0;
    }
}
// --- End of DB statistik yordamchilari ---
function db_get_users_page($page = 0, $per_page = 30, $sort_by = 'default') {
    $db = get_db_connection();
    if (!$db) return [];
    $offset = max(0, intval($page)) * intval($per_page);

    // safe whitelist for ordering
    $order_by = "created_at DESC";
    if ($sort_by === 'diamonds') $order_by = "diamonds DESC";
    elseif ($sort_by === 'coins') $order_by = "coins DESC";
    // note: more complex sorts (orders, referrals) require joins/extra queries

    $sql = "SELECT id, username, diamonds, coins, refs, confirmed_refs FROM users
            ORDER BY {$order_by} LIMIT ? OFFSET ?";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([intval($per_page), $offset]);
        $users = [];
        while ($row = $stmt->fetch()) {
            $row['refs'] = $row['refs'] ? json_decode($row['refs'], true) : [];
            $row['confirmed_refs'] = $row['confirmed_refs'] ? json_decode($row['confirmed_refs'], true) : [];
            $users[$row['id']] = $row;
        }
        return $users;
    } catch (PDOException $e) {
        error_log("db_get_users_page error: " . $e->getMessage());
        return [];
    }
}

// Delete user from MySQL
function delete_user($user_id) {
    $db = get_db_connection();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        return false;
    }
}

// Update specific user field
function update_user_field($user_id, $field, $value) {
    $db = get_db_connection();
    if (!$db) return false;
    
    try {
        $json_fields = ['refs', 'hashtags', 'confirmed_refs', 'channel_rewards'];
        if (in_array($field, $json_fields)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "UPDATE users SET $field = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$value, $user_id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Update user field error: " . $e->getMessage());
        return false;
    }
}
function load_json($path, $default = null) {
    if (!file_exists($path)) {
        file_put_contents($path, json_encode($default !== null ? $default : []));
        return $default !== null ? $default : [];
    }
    try {
        $data = json_decode(file_get_contents($path), true);
        return $data === null ? ($default !== null ? $default : []) : $data;
    } catch (Exception $e) {
        return $default !== null ? $default : [];
    }
}

function save_json($path, $data) {
    // Papka mavjudligini tekshirish
    $dir = dirname($path);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $temp_file = $path . '.tmp';
    $result = file_put_contents($temp_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    if ($result === false) {
        error_log("Faylga yozishda xatolik: " . $temp_file);
        return false;
    }
    
    // Temporary faylni asl faylga o'zgartirish
    if (!rename($temp_file, $path)) {
        error_log("Faylni almashtirishda xatolik: " . $path);
        return false;
    }
    
    return true;
}
function refresh_user_data($user_id) {
    global $users;
    $uid = strval($user_id);
    
    // Cache dan tozalash
    clear_cache("user_{$uid}");
    
    // MySQL dan yangi ma'lumot olish
    $fresh_user = get_user($uid);
    if ($fresh_user) {
        $users[$uid] = $fresh_user;
        return true;
    }
    return false;
}
create_users_table();

// Load users from MySQL
$users = []; // Lazy loading: foydalanuvchini faqat kerak bo'lganda DB dan yuklaymiz
$buttons = load_json(BUTTONS_FILE, []);
$videos = load_json(VIDEOS_META, []);
$config = load_json(CONFIG_FILE, ["channels" => [], "contact" => null, "admins" => ADMINS]);
$promocodes = load_json(PROMOCODES_FILE, []);
$orders = load_json(ORDERS_FILE, []);
$states = load_json(STATES_FILE, []);
$stickers = load_json(STICKERS_FILE, []);
$channel_ads = load_json(CHANNEL_ADS_FILE, []);
$promo_messages = load_json(PROMO_MESSAGES_FILE, []);

$ADMINS = isset($config['admins']) ? $config['admins'] : (defined('ADMINS') ? ADMINS : []);

function get_total_orders() {
    global $orders;
    return count($orders);
}

function get_total_promocode_diamonds() {
    global $promocodes;
    $total = 0;
    foreach ($promocodes as $promo) {
        if (!isset($promo['type']) || $promo['type'] !== 'coins') {
            $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
            $total += $used_count * $promo['amount'];
        }
    }
    return $total;
}

function get_total_promocode_coins() {
    global $promocodes;
    $total = 0;
    foreach ($promocodes as $promo) {
        if (isset($promo['type']) && $promo['type'] == 'coins') {
            $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
            $total += $used_count * $promo['amount'];
        }
    }
    return $total;
}

function calculate_delivery_time($views) {
    $views_per_hour = 13000;
    $hours = ceil($views / $views_per_hour);
    
    if ($hours <= 1) {
        return "⏰ Taxminiy bajarilish vaqti: 45 daqiqa ichida";
    } else {
        return "⏰ Taxminiy bajarilish vaqti: $hours soat ichida";
    }
}

function calculate_coins_for_shipments($shipments) {
    $coins = ceil($shipments * 3 / 100); // 100 tashish = 3 tanga
    return max(1, intval($coins));
}

function calculate_coins_for_likes($likes) {
    return max(1, intval(ceil($likes / 10)));
}

function sort_users_by_diamonds($users) {
    $sorted = $users;
    uasort($sorted, function($a, $b) {
        $diamonds_a = $a['diamonds'] ?? 0;
        $diamonds_b = $b['diamonds'] ?? 0;
        return $diamonds_b - $diamonds_a;
    });
    return $sorted;
}

function sort_users_by_coins($users) {
    $sorted = $users;
    uasort($sorted, function($a, $b) {
        $coins_a = $a['coins'] ?? 0;
        $coins_b = $b['coins'] ?? 0;
        return $coins_b - $coins_a;
    });
    return $sorted;
}

function sort_users_by_orders($users) {
    global $orders;
    $sorted = $users;
    
    uasort($sorted, function($a, $b) use ($orders, $users) {
        $user_id_a = array_search($a, $users);
        $user_id_b = array_search($b, $users);
        
        $orders_a = 0;
        $orders_b = 0;
        
        foreach ($orders as $order) {
            if ($order['user_id'] == $user_id_a) $orders_a++;
            if ($order['user_id'] == $user_id_b) $orders_b++;
        }
        
        return $orders_b - $orders_a;
    });
    return $sorted;
}

function sort_users_by_referrals($users) {
    $sorted = $users;
    uasort($sorted, function($a, $b) use ($users) {
        $user_id_a = array_search($a, $users);
        $user_id_b = array_search($b, $users);
        
        $refs_a = get_valid_ref_count($user_id_a);
        $refs_b = get_valid_ref_count($user_id_b);
        return $refs_b - $refs_a;
    });
    return $sorted;
}

function sort_users_by_total_referrals($users) {
    $sorted = $users;
    uasort($sorted, function($a, $b) {
        $total_refs_a = count($a['refs'] ?? []);
        $total_refs_b = count($b['refs'] ?? []);
        return $total_refs_b - $total_refs_a;
    });
    return $sorted;
}
function generate_user_list_page($page = 0, $sort_by = 'default') {
    global $orders, $promocodes;
    $users_per_page = 30;

    $total_users = db_get_users_count();
    $total_pages = ceil($total_users / $users_per_page);
    $total_pages = $total_pages > 0 ? $total_pages : 1;

    $db_users = db_get_users_page($page, $users_per_page, $sort_by);

    $message = "👥 FOYDALANUVCHILAR RO'YXATI\n";
    $sort_names = [
        'default' => '🆕 Yangi',
        'diamonds' => '💎 Olmos',
        'coins' => '🪙 Tanga',
        'orders' => '📦 Buyurtma',
        'referrals' => '✅ Tasdiqlangan referal',
        'total_referrals' => '👥 Jami referal'
    ];
    $message .= "📊 Tartib: " . ($sort_names[$sort_by] ?? $sort_names['default']) . "\n";
    $message .= "📄 Sahifa " . ($page + 1) . "/$total_pages\n";
    $message .= "👤 Jami foydalanuvchi: " . $total_users . " ta\n\n";

    if (empty($db_users)) {
        $message .= "📭 Foydalanuvchilar mavjud emas";
    } else {
        $i = $page * $users_per_page;
        foreach ($db_users as $user_id => $user) {
            $i++;
            $username = isset($user['username']) && $user['username'] ? "@" . $user['username'] : "ID: $user_id";
            $diamonds = $user['diamonds'] ?? 0;
            $coins = $user['coins'] ?? 0;
            // Ref counts — xavfsiz va DB/JSON fallback bilan
            $ref_counts = get_ref_counts($user_id);
            $ref_count = $ref_counts['confirmed'];                 // tasdiqlangan referallar
            $total_refs = $ref_counts['total'];                    // jami taklif qilinganlar
            $confirmed_with_bonus = $ref_counts['confirmed_with_bonus']; // tasdiqlangan + bonus_refs (agar ko'rsatmoqchi bo'lsangiz)
            // buyurtma sonini orders massividan hisoblash (orders kichik bo'lsa in-memory ishlaydi)
            $order_count = 0;
            foreach ($orders as $order) {
                if ($order['user_id'] == $user_id) $order_count++;
            }

            $promo_used_count = 0;
            foreach ($promocodes as $promo) {
                if (isset($promo['used_by']) && in_array($user_id, $promo['used_by'])) $promo_used_count++;
            }

            $message .= ($i) . ". $username\n";
            $message .= "   💎 Olmos: $diamonds\n";
            $message .= "   🪙 Tanga: $coins\n";
            $message .= "   👥 Referal: ✅ Tasdiqlangan: {$ref_count} ta (jami: {$total_refs} ta)";
            if ($confirmed_with_bonus > $ref_count) {
                $message .= " — (bonus hisoblangan: {$confirmed_with_bonus} ta)";
            }
            $message .= "\n";
            $message .= "   📦 Buyurtma: $order_count ta\n";
            $message .= "   🎁 Promokod: $promo_used_count ta\n\n";
        }
    }

    // inline keyboard — sahifalar va tartib tugmalari
    $inline_kb = [];

    $sort_buttons = [];
    $sorts = [
        'default' => '🆕',
        'diamonds' => '💎',
        'coins' => '🪙',
        'orders' => '📦',
        'referrals' => '✅',
        'total_referrals' => '👥'
    ];
    foreach ($sorts as $key => $text) {
        $sort_buttons[] = ["text" => $text, "callback_data" => "user_sort|{$key}|{$page}"];
    }
    $inline_kb[] = $sort_buttons;

    $page_buttons = [];
    if ($page > 0) {
        $page_buttons[] = ["text" => "⬅️ Ortga", "callback_data" => "user_list_page|{$sort_by}|" . ($page - 1)];
    }
    $page_buttons[] = ["text" => "🏠", "callback_data" => "main_menu"];
    if ($page < $total_pages - 1) {
        $page_buttons[] = ["text" => "Keyingi ➡️", "callback_data" => "user_list_page|{$sort_by}|" . ($page + 1)];
    }
    if (!empty($page_buttons)) $inline_kb[] = $page_buttons;

    return [
        'text' => $message,
        'reply_markup' => ["inline_keyboard" => $inline_kb]
    ];
}
function get_user_orders($user_identifier) {
    global $users, $orders;
    
    $target_user = null;
    foreach ($users as $user_id => $user_data) {
        if ($user_data['username'] == $user_identifier || $user_id == $user_identifier) {
            $target_user = $user_id;
            break;
        }
    }
    
    if (!$target_user) {
        return "❌ Foydalanuvchi topilmadi!";
    }
    
    $message = "📦 FOYDALANUVCHI BUYURTMALARI\n\n";
    $message .= "👤 Foydalanuvchi: " . ($users[$target_user]['username'] ? "@" . $users[$target_user]['username'] : "ID: $target_user") . "\n\n";
    
    $user_orders = [];
    foreach ($orders as $order_id => $order) {
        if ($order['user_id'] == $target_user) {
            $user_orders[] = $order;
        }
    }
    
    if (empty($user_orders)) {
        $message .= "📭 Buyurtmalar mavjud emas";
    } else {
        foreach ($user_orders as $index => $order) {
            $message .= ($index + 1) . ". ";
            if ($order['type'] == 'views') {
                $message .= "📹 " . ($order['video_title'] ?? 'Noma\'lum') . "\n";
                $message .= "   👁 Ko'rishlar: " . number_format($order['views']) . " ta\n";
                $message .= "   💎 Olmos: " . $order['diamonds'] . " ta\n";
            } elseif ($order['type'] == 'likes') {
                $message .= "❤️ Like xizmati\n";
                $message .= "   ❤️ Likelar: " . number_format($order['likes_count']) . " ta\n";
                $message .= "   🪙 Tanga: " . $order['coins'] . " ta\n";
            } elseif ($order['type'] == 'followers') {
                $message .= "👥 Obunachilar xizmati\n";
                $message .= "   👥 Obunachilar: " . number_format($order['followers_count']) . " ta\n";
                $message .= "   🪙 Tanga: " . $order['coins'] . " ta\n";
            }
            $message .= "   🔗 Link: " . $order['link'] . "\n";
            $message .= "   ⏰ Sana: " . date('d.m.Y H:i', strtotime($order['created_at'])) . "\n\n";
        }
    }
    
    return $message;
}

function create_likes_confirmation_kb($likes, $coins) {
    return [
        "inline_keyboard" => [
            [
                ["text" => "✅ Roziman", "callback_data" => "confirm_likes|{$likes}|{$coins}"],
                ["text" => "❌ Bekor qilish", "callback_data" => "cancel_likes"]
            ]
        ]
    ];
}

// create_followers_confirmation_kb funksiyasini O'CHIRIB, uning o'rniga quyidagini qo'ying:
function create_shipments_confirmation_kb($shipments, $coins) {
    return [
        "inline_keyboard" => [
            [
                ["text" => "✅ Roziman", "callback_data" => "confirm_shipments|{$shipments}|{$coins}"],
                ["text" => "❌ Bekor qilish", "callback_data" => "cancel_shipments"]
            ]
        ]
    ];
}
function check_and_reward_referrer($user_id) {
    $uid = strval($user_id);
    
    // Foydalanuvchini olish
    $user = get_user($uid);
    if (!$user) {
        error_log("check_and_reward_referrer: Foydalanuvchi topilmadi: {$uid}");
        return false;
    }
    
    // Referal ekanligini tekshirish
    if (!isset($user["ref_of"]) || !$user["ref_of"]) {
        return false;
    }
    
    $referrer_id = $user["ref_of"];
    $referrer = get_user($referrer_id);
    if (!$referrer) {
        error_log("check_and_reward_referrer: Referrer topilmadi: {$referrer_id}");
        return false;
    }
    
    // Foydalanuvchi tasdiqlanganligini tekshirish
    if (!isset($user["confirmed"]) || !$user["confirmed"]) {
        return false;
    }
    
    // Mukofot allaqachon berilganligini tekshirish
    if (isset($user["ref_reward_given"]) && $user["ref_reward_given"]) {
        return false;
    }
    
    // Mukofot berish
    $reward_amount = 15;
    
    // Referrer balansini yangilash
    $new_referrer_balance = update_user_balance($referrer_id, 'diamonds', $reward_amount, 'add');
    
    if ($new_referrer_balance === false) {
        error_log("check_and_reward_referrer: Referrer balansini yangilashda xatolik: {$referrer_id}");
        return false;
    }
    
    // Mukofot berilganligini belgilash
    $user["ref_reward_given"] = true;
    
    // Tasdiqlangan referallar ro'yxatini yangilash
    if (!isset($referrer["confirmed_refs"])) {
        $referrer["confirmed_refs"] = [];
    }
    
    // Agar allaqachon ro'yxatda bo'lmasa qo'shish
    if (!in_array($uid, $referrer["confirmed_refs"])) {
        $referrer["confirmed_refs"][] = $uid;
    }
    
    // Global users massivini yangilash
    global $users;
    $users[$uid] = $user;
    $users[$referrer_id] = $referrer;
    $users[$referrer_id]["diamonds"] = $new_referrer_balance; // Global massivni yangilash
    
    // MySQL ga saqlash
    $user_saved = save_user($uid, $user);
    $referrer_saved = save_user($referrer_id, $referrer);
    
    if (!$user_saved || !$referrer_saved) {
        error_log("check_and_reward_referrer: Foydalanuvchilarni saqlashda xatolik");
        return false;
    }
    
    // Xabarlarni yuborish
    $new_user_name = isset($user['username']) && $user['username'] ? 
        "@" . $user['username'] : "ID: " . $uid;
    
    $referrer_name = isset($referrer['username']) && $referrer['username'] ? 
        "@" . $referrer['username'] : "ID: " . $referrer_id;
    
    // Referrer ga xabar
    $old_balance = max(0, $new_referrer_balance - $reward_amount);
    bot_sendMessage($referrer_id,
        "🎉 Tabriklaymiz! Sizning taklif qilgan do'stingiz botdan foydalandi!\n\n" .
        "👤 {$new_user_name} foydalanuvchi sizning referal havolangiz orqali botga start bosdi va majburiy kanallarga obuna bo'ldi. Sizga +15 olmos berildi! ✅\n\n" .
        "💎 Avvalgi balans: " . $old_balance . " olmos\n" .
        "💎 Yangi balans: {$new_referrer_balance} olmos");
    
    // 5 ta tasdiqlangan referal uchun bonus
    $confirmed_count = count($referrer["confirmed_refs"]);
    if ($confirmed_count % 5 === 0 && $confirmed_count > 0) {
        $bonus_amount = 5;
        $new_bonus_balance = update_user_balance($referrer_id, 'diamonds', $bonus_amount, 'add');
        
        if ($new_bonus_balance !== false) {
            $referrer["bonus_refs"] = ($referrer["bonus_refs"] ?? 0) + 1;
            
            // Yana saqlash
            save_user($referrer_id, $referrer);
            $users[$referrer_id] = $referrer;
            $users[$referrer_id]["diamonds"] = $new_bonus_balance;
            
            bot_sendMessage($referrer_id,
                "🎉 Tabriklaymiz! Sizda {$confirmed_count} ta tasdiqlangan referal!\n" .
                "💎 Bonus: +{$bonus_amount} olmos\n" .
                "💎 Jami olmoslar: {$new_bonus_balance}");
        }
    }
    
    error_log("Referal mukofoti berildi: {$uid} -> {$referrer_id} (+{$reward_amount} olmos)");
    return true;
}
function get_promocode_statistics() {
    global $promocodes;
    
    if (empty($promocodes)) {
        return "🎁 Promokodlar mavjud emas";
    }
    
    $message = "📊 PROMOKOD STATISTIKASI\n\n";
    $total_diamonds_given = 0;
    $total_coins_given = 0;
    $total_users_used = 0;
    
    foreach ($promocodes as $name => $promo) {
        $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
        
        if (isset($promo['type']) && $promo['type'] == 'coins') {
            $coins_given = $used_count * $promo['amount'];
            $total_coins_given += $coins_given;
            
            $message .= "🔑 $name\n";
            $message .= "   🪙 Mukofot: " . $promo['amount'] . " tanga\n";
            $message .= "   👥 Foydalangan: $used_count ta\n";
            $message .= "   💰 Jami berilgan: $coins_given tanga\n";
        } else {
            $diamonds_given = $used_count * $promo['amount'];
            $total_diamonds_given += $diamonds_given;
            
            $message .= "🔑 $name\n";
            $message .= "   💎 Mukofot: " . $promo['amount'] . " olmos\n";
            $message .= "   👥 Foydalangan: $used_count ta\n";
            $message .= "   💰 Jami berilgan: $diamonds_given olmos\n";
        }
        
        if ($promo['limit_type'] == 'user_limit') {
            $remaining = $promo['limit'] - $used_count;
            $message .= "   📊 Limit: " . $promo['limit'] . " ta (qolgan: $remaining)\n";
        } else {
            $expires = isset($promo['expires_at']) ? date('d.m.Y H:i', strtotime($promo['expires_at'])) : 'Cheksiz';
            $message .= "   ⏰ Muddati: $expires\n";
        }
        $message .= "\n";
        
        $total_users_used += $used_count;
    }
    
    $message .= "📈 UMUMIY STATISTIKA:\n";
    $message .= "   💎 Jami berilgan olmos: $total_diamonds_given ta\n";
    $message .= "   🪙 Jami berilgan tanga: $total_coins_given ta\n";
    $message .= "   👥 Jami foydalanuvchi: $total_users_used ta\n";
    $message .= "   🎁 Jami promokod: " . count($promocodes) . " ta";
    
    return $message;
}

function make_statistics_kb() {
    return [
        "inline_keyboard" => [
            [["text" => "👥 Foydalanuvchilar ro'yxati", "callback_data" => "user_list"]],
            [["text" => "📦 Buyurtmalar ro'yxati", "callback_data" => "order_list"]],
            [["text" => "📊 Promokod statistika", "callback_data" => "promo_stats"]],
            [["text" => "✉️ Foydalanuvchiga xabar", "callback_data" => "send_user_message"]],
            [["text" => "🏠 Asosiy menyu", "callback_data" => "main_menu"]]
        ]
    ];
}

function send_video_to_channel($video_url, $username, $user_id, $warning_count = 0) {
    $message = "🎬 YANGI VIDEO BUYURTMASI 🎬\n\n";
    $message .= "👤 Foydalanuvchi: @" . ($username ?: $user_id) . "\n";
    $message .= "🆔 ID: $user_id\n";
    $message .= "🔗 Video havolasi: $video_url\n";
    $message .= "⚠️ Ogohlantirishlar soni: $warning_count ta\n\n";
    $message .= "Admin harakatini tanlang:";
    
    $kb = [
        "inline_keyboard" => [
            [
                ["text" => "⚠️ Ogohlantirish", "callback_data" => "admin_warning|{$user_id}|{$video_url}"],
                ["text" => "💎 Jarima", "callback_data" => "admin_penalty|{$user_id}|{$video_url}"],
                ["text" => "✅ Muammo yo'q", "callback_data" => "admin_ok|{$user_id}|{$video_url}"]
            ]
        ]
    ];
    
    return bot_sendMessage(PROOF_CHANNEL, $message, $kb);
}

function get_instagram_video_title($video_url) {
    return 'Instagram video';
}

function check_instagram_profile_exists($profile_url) {
    if (!function_exists('curl_init')) {
        $headers = @get_headers($profile_url, 1);
        if ($headers === false) return false;
        $status_line = is_array($headers) && isset($headers[0]) ? $headers[0] : '';
        if (strpos($status_line, '200') === false) return false;
        return true;
    }

    $ch = curl_init($profile_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Bot/1.0)");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return false;
    }

    if (in_array($http_code, [404, 410])) {
        return false;
    }

    if (!$body || strlen($body) < 50) {
        if (strpos($effective_url, 'instagram.com') !== false && $http_code >= 200 && $http_code < 400) {
            return true;
        }
        return false;
    }

    $lower = strtolower($body);

    if (strpos($lower, 'page not found') !== false
        || strpos($lower, 'sorry, this page isn') !== false
        || strpos($lower, 'the link you followed may be broken') !== false) {
        return false;
    }

    if (strpos($body, 'window._sharedData') !== false || strpos($body, 'profilePage_') !== false) {
        return true;
    }

    if (preg_match('/<meta[^>]+property=["\']og:type["\'][^>]*content=["\']profile["\']/i', $body)
        || preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $body)) {
        return true;
    }

    if (strpos($body, 'edge_followed_by') !== false || strpos($body, 'edge_follow') !== false) {
        return true;
    }

    if (strpos($effective_url, 'instagram.com') !== false && $http_code >= 200 && $http_code < 400) {
        return true;
    }

    return false;
}

function handle_api_error($error_message, $user_id = null, $username = null) {
    error_log("API Error: " . $error_message);
    
    if ($user_id) {
        bot_sendMessage($user_id, 
            "❌ Texnik xatolik yuz berdi. Iltimos, birozdan keyin qayta urinib ko'ring.\n\n" .
            "Agar muammo takrorlansa, admin bilan bog'laning."
        );
    }
    
    if (strpos($error_message, 'timeout') !== false || strpos($error_message, 'connection') !== false) {
        foreach (ADMINS as $admin) {
            bot_sendMessage($admin, 
                "⚠️ API XATOSI\n" .
                "Xatolik: " . $error_message . "\n" .
                ($username ? "Foydalanuvchi: @{$username}\n" : "") .
                ($user_id ? "ID: {$user_id}" : ""));
        }
    }
}

function get_user_state($user_id) {
    global $states;
    return isset($states[$user_id]) ? $states[$user_id] : null;
}

function set_user_state($user_id, $state, $data = []) {
    global $states;
    $states[$user_id] = [
        'state' => $state,
        'data' => $data
    ];
    save_json(STATES_FILE, $states);
}

function clear_user_state($user_id) {
    global $states;
    if (isset($states[$user_id])) {
        unset($states[$user_id]);
        save_json(STATES_FILE, $states);
    }
}

function get_state_data($user_id, $key = null) {
    global $states;
    if (!isset($states[$user_id]) || !isset($states[$user_id]['data'])) {
        return null;
    }
    if ($key === null) {
        return $states[$user_id]['data'];
    }
    return isset($states[$user_id]['data'][$key]) ? $states[$user_id]['data'][$key] : null;
}

function set_state_data($user_id, $key, $value) {
    global $states;
    if (!isset($states[$user_id])) {
        $states[$user_id] = ['state' => '', 'data' => []];
    }
    $states[$user_id]['data'][$key] = $value;
    save_json(STATES_FILE, $states);
}
function check_shipments_service_flow() {
    global $users, $states;
    
    // "Jo'natishlar" xizmati flow ini tekshirish
    $shipments_flow_working = false;
    
    foreach ($states as $user_id => $state_data) {
        if (strpos($state_data['state'], 'shipments') !== false) {
            $shipments_flow_working = true;
            break;
        }
    }
    
    return $shipments_flow_working;
}
function send_sticker($chat_id, $sticker_type) {
    global $stickers;
    if (isset($stickers[$sticker_type]) && $stickers[$sticker_type]) {
        bot_sendSticker($chat_id, $stickers[$sticker_type]);
        return true;
    }
    return false;
}

function send_button_sticker($chat_id, $button_text) {
    global $stickers;
    
    $sticker_key = 'button_' . md5($button_text);
    
    if (isset($stickers[$sticker_key]) && $stickers[$sticker_key]) {
        bot_sendSticker($chat_id, $stickers[$sticker_key]);
        return true;
    }
    
    return false;
}

function calculate_diamonds_for_views($views) {
    if ($views >= 100000) return intval($views * 0.0075);
    elseif ($views >= 50000) return intval($views * 0.008);
    elseif ($views >= 20000) return intval($views * 0.0085);
    elseif ($views >= 10000) return intval($views * 0.009);
    elseif ($views >= 5000) return intval($views * 0.009);
    elseif ($views >= 1000) return intval($views * 0.01);
    else return -1;
}

function get_admin_contact_url() {
    global $config;
    $admin = null;
    if (defined('ADMINS')) {
        $admins = ADMINS;
        if (is_array($admins) && count($admins) > 0) {
            $admin = $admins[0];
        } elseif (is_string($admins) && $admins !== '') {
            $admin = $admins;
        }
    }
    if (empty($admin) && isset($config['contact']) && $config['contact']) {
        $admin = $config['contact'];
    }
    if (!$admin) $admin = 'admin';
    return 'https://t.me/' . ltrim($admin, '@');
}

function make_shop_contact_kb($type = 'diamonds') {
    $url = get_admin_contact_url();
    $text = $type === 'coins' ? "🪙 Tanga sotib olish" : "💎 Olmos sotib olish";
    return [
        "inline_keyboard" => [
            [
                ["text" => $text, "url" => $url]
            ]
        ]
    ];
}

function get_hashtags($v) {
    $hashtags = [
        "#Videolaringizni rekga chiqaradigan suniy intelektni hohlaysizmi? Telegramga RekchiAi_bot ga kiring.",
        "#Telegramdagi",
        "#RekchiAi_bot"
    ];
    
    return $hashtags;
}

function get_valid_ref_count($uid) {
    global $users;

    // Agar global massivda foydalanuvchi mavjud bo'lsa, undan foydalanamiz
    if (isset($users[$uid])) {
        $user = $users[$uid];
    } else {
        // Aks holda MySQL dan olamiz (get_user() JSON decode qiladi)
        $user = get_user($uid);
        if (!$user) return 0;
    }

    // Tasdiqlangan referallar (confirmed_refs) — xavfsiz hisoblash
    $confirmed_refs = [];
    if (isset($user['confirmed_refs']) && is_array($user['confirmed_refs'])) {
        $confirmed_refs = $user['confirmed_refs'];
    } elseif (!empty($user['confirmed_refs'])) {
        $decoded = json_decode($user['confirmed_refs'], true);
        $confirmed_refs = is_array($decoded) ? $decoded : [];
    }

    // Jami confirmed referallar soni
    $confirmed_count = count($confirmed_refs);

    // bonus_refs may be stored in DB or in-memory
    $bonus_refs = isset($user['bonus_refs']) ? intval($user['bonus_refs']) : 0;

    return $confirmed_count + $bonus_refs;
}

function bot_sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function bot_sendSticker($chat_id, $sticker) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendSticker";
    $data = [
        'chat_id' => $chat_id,
        'sticker' => $sticker
    ];
    
    return send_telegram_request($url, $data);
}

function bot_editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/editMessageText";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ];
    
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function bot_deleteMessage($chat_id, $message_id) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/deleteMessage";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    
    return send_telegram_request($url, $data);
}

function bot_sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendPhoto";
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo
    ];
    
    if ($caption) $data['caption'] = $caption;
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function bot_sendVideo($chat_id, $video, $caption = null, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendVideo";
    $data = [
        'chat_id' => $chat_id,
        'video' => $video
    ];
    
    if ($caption) $data['caption'] = $caption;
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function bot_sendDocument($chat_id, $document, $caption = null, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/sendDocument";
    $data = [
        'chat_id' => $chat_id,
        'document' => $document
    ];
    
    if ($caption) $data['caption'] = $caption;
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function bot_editMessageCaption($chat_id, $message_id, $caption, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/editMessageCaption";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'caption' => $caption
    ];
    
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    
    return send_telegram_request($url, $data);
}

function answer_callback_query($callback_query_id, $text = null, $show_alert = false) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/answerCallbackQuery";
    $data = ['callback_query_id' => $callback_query_id];
    
    if ($text) $data['text'] = $text;
    if ($show_alert) $data['show_alert'] = true;
    
    return send_telegram_request($url, $data);
}

function send_telegram_request($url, $data) {
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    $decoded = $result ? json_decode($result, true) : false;

    error_log("Telegram request to {$url} with data: " . json_encode($data, JSON_UNESCAPED_UNICODE));
    if ($result === FALSE) {
        $err = error_get_last();
        error_log("Telegram request failed: " . ($err['message'] ?? 'unknown'));
    } else {
        error_log("Telegram response: " . $result);
    }

    return $decoded;
}

function send_video_to_channel_by_order($order_id) {
    global $orders, $users;

    if (!isset($orders[$order_id])) {
        error_log("send_video_to_channel_by_order: order not found: {$order_id}");
        return false;
    }

    $order = $orders[$order_id];
    $user_id = $order['user_id'];
    $username = $order['username'] ?? '';
    $video_url = $order['link'];
    $views_count = $order['views'];
    $diamonds = $order['diamonds'];
    $warning_count = $users[$user_id]['hashtag_warnings'] ?? 0;

    $message = "🎬 YANGI VIDEO BUYURTMASI 🎬\n\n";
    $message .= "👤 Foydalanuvchi: " . ($username ? "@{$username}" : "ID: {$user_id}") . "\n";
    $message .= "🆔 ID: {$user_id}\n";
    $message .= "👁 Ko'rishlar soni: " . number_format($views_count) . " ta\n";
    $message .= "💎 Sarflangan olmos: {$diamonds} ta\n";
    $message .= "🔗 Video havolasi: {$video_url}\n";
    $message .= "⚠️ Ogohlantirishlar soni: {$warning_count} ta\n\n";
    $message .= "Admin harakatini tanlang:";

    $kb = [
        "inline_keyboard" => [
            [
                ["text" => "⚠️ Ogohlantirish", "callback_data" => "admin_warning_order|{$order_id}"],
                ["text" => "💎 Jarima", "callback_data" => "admin_penalty_order|{$order_id}"],
                ["text" => "✅ Muammo yo'q", "callback_data" => "admin_ok_order|{$order_id}"]
            ]
        ]
    ];

    $res = bot_sendMessage(PROOF_CHANNEL, $message, $kb);
    if (!$res || !isset($res['ok']) || !$res['ok']) {
        error_log("Failed to send video to proof channel for order {$order_id}: " . json_encode($res));
        return false;
    }
    return $res;
}
function bot_getChatMember($chat_id, $user_id) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/getChatMember";
    $data = [
        'chat_id' => $chat_id,
        'user_id' => intval($user_id)
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result ? json_decode($result, true) : false;
}

function is_user_subscribed_to_required_channels($user_id) {
    global $config;
    
    if (empty($config['channels'])) {
        error_log("Majburiy kanallar ro'yxati bo'sh");
        return true;
    }
    
    foreach ($config['channels'] as $ch) {
        $res = bot_getChatMember($ch, $user_id);
        
        error_log("Kanal tekshirish: User {$user_id} - Channel {$ch} - Result: " . json_encode($res));
        
        if (!$res || !isset($res['ok']) || $res['ok'] !== true) {
            error_log("Kanal tekshirish xatosi: " . $ch);
            return false;
        }
        
        $status = isset($res['result']['status']) ? $res['result']['status'] : '';
        error_log("Kanal holati: {$ch} - Status: {$status}");
        
        if (!in_array($status, ['member', 'creator', 'administrator'])) {
            error_log("Obuna topilmadi: {$ch} - Status: {$status}");
            return false;
        }
    }
    
    error_log("Barcha kanallarga obuna bo'lingan: User {$user_id}");
    return true;
}
function build_subscribe_kb() {
    global $config;
    $inline = [];
    if (empty($config['channels'])) {
        $inline[] = [["text" => "🔔 Kanal: " . PROOF_CHANNEL, "url" => "https://t.me/" . ltrim(PROOF_CHANNEL, "@")]];
    } else {
        foreach ($config['channels'] as $ch) {
            if (strpos($ch, '@') === 0) {
                $url = "https://t.me/" . substr($ch, 1);
            } else {
                $url = "https://t.me/" . ltrim($ch, "@");
            }
            $inline[] = [["text" => "🔔 Obuna bo'ling: " . (strpos($ch, '@')===0 ? $ch : $ch), "url" => $url]];
        }
    }
    $inline[] = [["text" => "✅ Tekshirish", "callback_data" => "check_sub"]];
    return ["inline_keyboard" => $inline];
}
function ensure_subscribed_or_prompt($chat_id, $user_id, $username = "", $message_id = null, $is_callback = false, $callback_query_id = null) {
    send_sticker($chat_id, 'subscribe_prompt');
    
    $uid = strval($user_id);
    
    // Avval foydalanuvchi ma'lumotlarini yangilab olish
    refresh_user_data($uid);
    
    // Agar obuna bo'lgan bo'lsa
    if (is_user_subscribed_to_required_channels($user_id)) {
        global $users;
        
        $users[$uid]["confirmed"] = true;
        
        // **MUHIM: Faqat BIRINCHI MARTA obuna bo'lganda bonus berish**
        // Agar video_watched = false bo'lsa (ya'ni start bonus OLINMAGAN bo'lsa)
        if (!isset($users[$uid]["video_watched"]) || !$users[$uid]["video_watched"]) {
            $bonus_diamonds = 12;
            
            // Balansni yangilash funksiyasidan foydalanish
            $new_balance = update_user_balance($uid, 'diamonds', $bonus_diamonds, 'add');
            
            if ($new_balance !== false) {
                $users[$uid]["video_watched"] = true; // BONUS BERILGANLIGINI BELGILASH
                $users[$uid]["diamonds"] = $new_balance; // Global massivni yangilash
                
                send_sticker($chat_id, 'subscribe_success');
                
                $bonus_message = "✅ Obuna tasdiqlandi!\n" .
                               "💎 Start bonus: +{$bonus_diamonds} olmos\n" .
                               "💎 Jami olmoslar: <b>{$new_balance}</b>\n\n" .
                               "⚠️ Eslatma: Start bonus faqat bir marta beriladi!";
                
                if ($is_callback && $message_id !== null) {
                    bot_editMessageText($chat_id, $message_id, $bonus_message, null, "HTML");
                } else {
                    bot_sendMessage($chat_id, $bonus_message, null, "HTML");
                }
                
                save_user($uid, $users[$uid]);
                
                // **Referal mukofotini tekshirish - faqat agar mukofot berilmagan bo'lsa**
                if (isset($users[$uid]["ref_of"]) && $users[$uid]["ref_of"] && (!isset($users[$uid]["ref_reward_given"]) || !$users[$uid]["ref_reward_given"])) {
                    $result = check_and_reward_referrer($user_id);
                    if ($result) {
                        error_log("Obuna tekshirishda referal mukofoti berildi: " . $uid);
                    }
                }
                
                // ASOSIY MENYUNI OCHISH
                bot_sendMessage($chat_id, " ", get_reply_keyboard($username));
                
            } else {
                // Balans yangilashda xatolik bo'lsa
                $error_message = "✅ Obuna tasdiqlandi! (Bonus berishda xatolik)";
                if ($is_callback && $message_id !== null) {
                    bot_editMessageText($chat_id, $message_id, $error_message);
                } else {
                    bot_sendMessage($chat_id, $error_message);
                }
                
                // ASOSIY MENYUNI OCHISH
                bot_sendMessage($chat_id, " ", get_reply_keyboard($username));
            }
            
        } else {
            // Agar bonus allaqachon berilgan bo'lsa
            $current_diamonds = get_user_balance($uid, 'diamonds');
            $no_bonus_message = "✅ Obuna tasdiqlandi!\n\n" .
                              "ℹ️ Siz start bonusni allaqachon olgansiz!\n" .
                              "💎 Jami olmoslar: <b>{$current_diamonds}</b>";
            
            if ($is_callback && $message_id !== null) {
                bot_editMessageText($chat_id, $message_id, $no_bonus_message, null, "HTML");
            } else {
                bot_sendMessage($chat_id, $no_bonus_message, null, "HTML");
            }
            
            // ASOSIY MENYUNI OCHISH
            bot_sendMessage($chat_id, " ", get_reply_keyboard($username));
        }
        
        if ($callback_query_id) {
            answer_callback_query($callback_query_id, "✅ Obuna tasdiqlandi!");
        }
        
        return true;
    }
    
    // Agar obuna bo'lmagan bo'lsa
    $kb = build_subscribe_kb();
    
    // Bonus holatiga qarab xabar tayyorlash
    $bonus_status = "💎 Start bonus: +12 olmos (faqat bir marta)";
    if (isset($users[$uid]["video_watched"]) && $users[$uid]["video_watched"]) {
        $bonus_status = "ℹ️ Siz start bonusni allaqachon olgansiz";
    }
    
    $text_msg = "🔔 Iltimos, quyidagi kanallarga obuna bo'ling, so'ng ✅ Tekshirish tugmasini bosing.\n\n" .
                $bonus_status;
    
    if ($is_callback && $message_id !== null) {
        bot_editMessageText($chat_id, $message_id, $text_msg, $kb);
        if ($callback_query_id) answer_callback_query($callback_query_id, "Iltimos, kanalga obuna bo'ling.", true);
    } else {
        bot_sendMessage($chat_id, $text_msg, $kb);
    }
    
    return false;
}
function create_channel_bonus_kb($channel_id) {
    return [
        "inline_keyboard" => [
            [["text" => "📢 Kanalga obuna bo'lish", "url" => "https://t.me/" . ltrim($channel_id, "@")]],
            [["text" => "✅ Obuna bo'ldim", "callback_data" => "channel_subscribed|{$channel_id}"]]
        ]
    ];
}

function check_channel_subscription($user_id, $channel_id) {
    $res = bot_getChatMember($channel_id, $user_id);
    if (!$res || !isset($res['ok']) || $res['ok'] !== true) {
        return false;
    }
    $status = isset($res['result']['status']) ? $res['result']['status'] : '';
    return in_array($status, ['member', 'creator', 'administrator']);
}
function process_channel_subscription($user_id, $channel_id) {
    global $users, $channel_ads;
    
    $uid = strval($user_id);
    
    // Avval foydalanuvchi ma'lumotlarini yangilab olish
    refresh_user_data($uid);
    
    if (!isset($channel_ads[$channel_id])) {
        error_log("Kanal topilmadi: {$channel_id}");
        return "channel_not_found";
    }
    
    $ad = $channel_ads[$channel_id];
    $reward = $ad['reward'];
    
    // Foydalanuvchi mavjudligini qattiq tekshirish
    if (!isset($users[$uid])) {
        error_log("Foydalanuvchi topilmadi: {$uid}");
        return "user_not_found";
    }
    
    // Agar foydalanuvchi allaqachon bonus olgan bo'lsa
    if (isset($users[$uid]['channel_rewards']) && is_array($users[$uid]['channel_rewards'])) {
        if (in_array($channel_id, $users[$uid]['channel_rewards'])) {
            error_log("Bonus allaqachon berilgan: {$uid} -> {$channel_id}");
            return "already_claimed";
        }
    }
    
    // Obunani qattiq tekshirish
    if (!check_channel_subscription($user_id, $channel_id)) {
        error_log("Obuna topilmadi: {$uid} -> {$channel_id}");
        return "not_subscribed";
    }
    
    // Bonus berish
    $new_balance = update_user_balance($uid, 'diamonds', $reward, 'add');
    
    if ($new_balance !== false) {
        // Channel rewards massivini ishga tushirish
        if (!isset($users[$uid]['channel_rewards']) || !is_array($users[$uid]['channel_rewards'])) {
            $users[$uid]['channel_rewards'] = [];
        }
        
        // Kanalni rewards ro'yxatiga qo'shish
        if (!in_array($channel_id, $users[$uid]['channel_rewards'])) {
            $users[$uid]['channel_rewards'][] = $channel_id;
        }
        
        // Vaqtni saqlash
        if (!isset($users[$uid]['channel_reward_time'])) {
            $users[$uid]['channel_reward_time'] = [];
        }
        $users[$uid]['channel_reward_time'][$channel_id] = time();
        
        // Global massivni yangilash
        $users[$uid]['diamonds'] = $new_balance;
        
        // Foydalanuvchini darhol saqlash
        $save_result = save_user($uid, $users[$uid]);
        
        if ($save_result) {
            error_log("Bonus muvaffaqiyatli berildi: {$uid} -> {$channel_id} (+{$reward} olmos)");
            return "success";
        } else {
            error_log("Bonus berildi, lekin saqlashda xatolik: {$uid}");
            return "save_error";
        }
    } else {
        error_log("Balans yangilashda xatolik: {$uid}");
        return "balance_error";
    }
}

function check_and_penalize_unsubscribed_users() {
    global $users, $channel_ads;
    
    $penalized_users = 0;
    
    foreach ($users as $user_id => $user_data) {
        if (isset($user_data['channel_rewards']) && !empty($user_data['channel_rewards'])) {
            foreach ($user_data['channel_rewards'] as $channel_id) {
                if (isset($channel_ads[$channel_id])) {
                    // Agar foydalanuvchi kanaldan chiqib ketgan bo'lsa
                    if (!check_channel_subscription($user_id, $channel_id)) {
                        $reward = $channel_ads[$channel_id]['reward'];
                        $penalty_amount = $reward + 1;
                        
                        $current_diamonds = $user_data['diamonds'] ?? 0;
                        
                        if ($current_diamonds >= $penalty_amount) {
                            // Jarima berish
                            $new_balance = update_user_balance($user_id, 'diamonds', $penalty_amount, 'subtract');
                            
                            if ($new_balance !== false) {
                                // Kanalni rewards ro'yxatidan o'chirish
                                $users[$user_id]['channel_rewards'] = array_diff(
                                    $users[$user_id]['channel_rewards'], 
                                    [$channel_id]
                                );
                                
                                if (isset($users[$user_id]['channel_reward_time'][$channel_id])) {
                                    unset($users[$user_id]['channel_reward_time'][$channel_id]);
                                }
                                
                                $penalized_users++;
                                
                                bot_sendMessage($user_id,
                                    "🚫 JARIMA BERILDI!\n\n" .
                                    "Siz {$channel_id} kanalidan obunani bekor qilgansiz!\n\n" .
                                    "📊 Tafsilotlar:\n" .
                                    "💎 Siz olgan mukofot: {$reward} olmos\n" .
                                    "💎 Jarima miqdori: -{$penalty_amount} olmos\n" .
                                    "💎 Qolgan olmoslar: {$new_balance}\n\n" .
                                    "⚠️ Eslatma: Kanalga qayta obuna bo'lsangiz ham, mukofot qayta berilmaydi!"
                                );
                                
                                save_user($user_id, $users[$user_id]);
                            }
                        } else {
                            // Balans yetarli bo'lmasa, faqat ro'yxatdan o'chirish
                            $users[$user_id]['channel_rewards'] = array_diff(
                                $users[$user_id]['channel_rewards'], 
                                [$channel_id]
                            );
                            
                            if (isset($users[$user_id]['channel_reward_time'][$channel_id])) {
                                unset($users[$user_id]['channel_reward_time'][$channel_id]);
                            }
                            
                            bot_sendMessage($user_id,
                                "⚠️ OGOHLANTIRISH!\n\n" .
                                "Siz {$channel_id} kanalidan obunani bekor qilgansiz!\n" .
                                "Jarima miqdori: {$penalty_amount} olmos\n" .
                                "Lekin sizda yetarli olmos yo'q, shuning uchun faqat kanal ro'yxatidan o'chirildi.\n\n" .
                                "💎 Qolgan olmoslar: {$current_diamonds}"
                            );
                            
                            save_user($user_id, $users[$user_id]);
                        }
                    }
                }
            }
        }
    }
    
    return $penalized_users;
}

function schedule_penalty_checks() {
    $current_time = time();
    $last_check = file_exists(PENALTY_CHECK_FILE) ? intval(file_get_contents(PENALTY_CHECK_FILE)) : 0;
    
    if ($current_time - $last_check >= 60) {
        $penalized_count = check_and_penalize_unsubscribed_users();
        file_put_contents(PENALTY_CHECK_FILE, $current_time);
        
        if ($penalized_count > 0) {
            foreach (ADMINS as $admin) {
                bot_sendMessage($admin, 
                    "📊 Jarima hisoboti (1 soatlik):\n" .
                    "👤 Jarimalangan foydalanuvchilar: {$penalized_count} ta\n" .
                    "⏰ Vaqt: " . date('Y-m-d H:i:s')
                );
            }
        }
    }
}

function send_bonus_channel_notification($channel_id, $reward, $added_by) {
    $message = "🎁 Kanalga obuna bo'lib, obuna bo'ldim tugmasini bosing va +{$reward} 💎 olmosni qo'lga kiriting ✅";
    
    $kb = [
        "inline_keyboard" => [
            [["text" => "📢 Kanalga obuna bo'lish", "url" => "https://t.me/" . ltrim($channel_id, "@")]],
            [["text" => "✅ Obuna bo'ldim", "callback_data" => "channel_subscribed|{$channel_id}"]]
        ]
    ];
    
    return bot_sendMessage("@insta_rekchi", $message, $kb);
}
function show_bonus_channels($chat_id, $user_id) {
    global $channel_ads, $users;
    
    if (empty($channel_ads)) {
        bot_sendMessage($chat_id, "🎁 Hozirda bonus kanallar mavjud emas.");
        return;
    }
    
    $uid = strval($user_id);
    $message = "🎁 BONUS KANALLAR - OLMOS YEG'ISH 🎁\n\n";
    
    foreach ($channel_ads as $channel_id => $ad) {
        $reward = $ad['reward'];
        $penalty = $reward + 1;
        $is_claimed = isset($users[$uid]['channel_rewards']) && in_array($channel_id, $users[$uid]['channel_rewards']);
        $status = $is_claimed ? "✅ OLINGAN" : "💎 +{$reward} olmos";
        
        $message .= "📢 {$channel_id}\n";
        $message .= "💎 Mukofot: {$reward} olmos\n";
        $message .= "🚫 Jarima: {$penalty} olmos (obunani bekor qilsangiz)\n";
        $message .= "📊 Holat: {$status}\n\n";
    }
    
    $message .= "Kanalga obuna bo'ling va 'Obuna bo'ldim' tugmasini bosing!\n";
    $message .= "⚠️ Diqqat: Kanaldan chiqib ketsangiz, jarima beriladi!";
    
    $first_channel = array_key_first($channel_ads);
    $kb = create_channel_bonus_kb($first_channel);
    bot_sendMessage($chat_id, $message, $kb);
}

function boost_instagram_views($video_url, $views_count) {
    $api_url = NEOSMM_API_URL;
    $data = [
        'key' => NEOSMM_API_KEY,
        'action' => 'add',
        'service' => NEOSMM_VIEWS_SERVICE_ID,
        'link' => $video_url,
        'quantity' => $views_count
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($api_url, false, $context);
    
    if ($result) {
        $response = json_decode($result, true);
        return $response;
    }
    
    return false;
}

function boost_instagram_likes($video_url, $likes_count) {
    $api_url = NEOSMM_API_URL;
    $data = [
        'key' => NEOSMM_API_KEY,
        'action' => 'add',
        'service' => LIKES_SERVICE_ID,
        'link' => $video_url,
        'quantity' => $likes_count
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($api_url, false, $context);
    
    if ($result) {
        $response = json_decode($result, true);
        return $response;
    }
    
    return false;
}

// boost_instagram_followers funksiyasini O'CHIRIB, uning o'rniga quyidagini qo'ying:
function boost_instagram_shipments($video_url, $shipments_count) {
    $api_url = NEOSMM_API_URL;
    $data = [
        'key' => NEOSMM_API_KEY,
        'action' => 'add',
        'service' => SHIPMENTS_SERVICE_ID,
        'link' => $video_url,
        'quantity' => $shipments_count
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($api_url, false, $context);
    
    if ($result) {
        $response = json_decode($result, true);
        return $response;
    }
    
    return false;
}

function send_order_completion_notification($order_id) {
    global $orders, $users;
    
    if (!isset($orders[$order_id])) return false;
    
    $order = $orders[$order_id];
    $user_id = $order['user_id'];
    $username = $order['username'];
    
    $proof_msg = "🔥 Buyurtma bajarildi 🔥\n\n";
    
    if ($order['type'] == 'views') {
        $proof_msg .= "👁 Prasmotr soni: " . number_format($order['views']) . "\n";
        $proof_msg .= "💎 Sarflangan olmos: " . $order['diamonds'] . "\n";
    } elseif ($order['type'] == 'likes') {
        $proof_msg .= "❤️ Like soni: " . number_format($order['likes_count']) . "\n";
        $proof_msg .= "🪙 Sarflangan tanga: " . $order['coins'] . "\n";
    } elseif ($order['type'] == 'followers') {
        $proof_msg .= "👥 Obunachilar soni: " . number_format($order['followers_count']) . "\n";
        $proof_msg .= "🪙 Sarflangan tanga: " . $order['coins'] . "\n";
    }
    
    $proof_msg .= "👤 Buyurtmachi: @" . ($username ?: $user_id);
    
    $proof_kb = [
        "inline_keyboard" => [
            [["text" => "🎬 Video ko'rish", "url" => $order['link']]],
            [["text" => "👁 Isbotlar kanali", "url" => "https://t.me/rekchi_tv"]]
        ]
    ];
    
    return bot_sendMessage(PROOF_CHANNEL, $proof_msg, $proof_kb);
}
// Funksiyalar qismiga qo'shing
function find_user_by_identifier($identifier) {
    global $users;
    
    $clean_identifier = ltrim($identifier, '@');
    
    // 1. Global massivda username bo'yicha qidirish
    foreach ($users as $uid => $user_data) {
        if (isset($user_data['username']) && 
            (strcasecmp($user_data['username'], $clean_identifier) === 0 || 
             strcasecmp($user_data['username'], $identifier) === 0)) {
            return $uid;
        }
    }
    
    // 2. ID bo'yicha qidirish
    if (is_numeric($identifier) && isset($users[$identifier])) {
        return $identifier;
    }
    
    // 3. DB dan qidirish
    if (is_numeric($identifier)) {
        $db_user = get_user($identifier);
        if ($db_user) {
            $users[$identifier] = $db_user;
            return $identifier;
        }
    }
    
    // 4. DB dan username bo'yicha qidirish
    $all_users = load_users();
    foreach ($all_users as $uid => $user_data) {
        if (isset($user_data['username']) && 
            (strcasecmp($user_data['username'], $clean_identifier) === 0 || 
             strcasecmp($user_data['username'], $identifier) === 0)) {
            $users[$uid] = $user_data;
            return $uid;
        }
    }
    
    return null;
}
function send_video_tutorial($chat_id, $video_id, $is_first_time = false) {
    global $videos;
    
    if (!isset($videos[$video_id])) return false;
    
    $video = $videos[$video_id];
    
    if (isset($video['file_id'])) {
        $caption = "🎬 {$video['title']}\n\n{$video['desc']}";
        
        if ($is_first_time) {
            $kb = [
                "inline_keyboard" => [
                    [["text" => "✅ Men videoni ko'rdim va tushundim", "callback_data" => "video_watched"]]
                ]
            ];
            bot_sendVideo($chat_id, $video['file_id'], $caption, $kb);
        } else {
            bot_sendVideo($chat_id, $video['file_id'], $caption);
        }
        return true;
    }
    elseif (isset($video['link'])) {
        $caption = "🎬 {$video['title']}\n\n{$video['desc']}";
        
        if ($is_first_time) {
            $kb = [
                "inline_keyboard" => [
                    [["text" => "🎬 Videoni ko'rish", "url" => $video['link']]],
                    [["text" => "✅ Men videoni ko'rdim va tushundim", "callback_data" => "video_watched"]]
                ]
            ];
            bot_sendMessage($chat_id, $caption, $kb);
        } else {
            $kb = [
                "inline_keyboard" => [
                    [["text" => "🎬 Videoni ko'rish", "url" => $video['link']]]
                ]
            ];
            bot_sendMessage($chat_id, $caption, $kb);
        }
        return true;
    }
    
    return false;
}

function process_successful_order($order_id) {
    global $orders, $users;
        
    if (!isset($orders[$order_id])) return false;
                
    $order = $orders[$order_id];
    $user_id = $order['user_id'];
                            
    if (!isset($users[$user_id])) return false;
                                    
    if ($order['type'] == 'views') {
        $diamonds_needed = $order['diamonds'];
        $current_diamonds = $users[$user_id]["diamonds"] ?? 0;
                                                
        if ($current_diamonds >= $diamonds_needed) {
            $users[$user_id]["diamonds"] -= $diamonds_needed;
            $users[$user_id]["used_diamonds"] = ($users[$user_id]["used_diamonds"] ?? 0) + $diamonds_needed;
        } else {
            return false;
        }
    } elseif ($order['type'] == 'likes' || $order['type'] == 'followers') {
        $coins_needed = $order['coins'];
        $current_coins = $users[$user_id]["coins"] ?? 0;
        
        if ($current_coins >= $coins_needed) {
            $users[$user_id]["coins"] -= $coins_needed;
        } else {
            return false;
        }
    }
                                                                            
    if (isset($users[$user_id]["ref_of"])) {
        $ref_id = $users[$user_id]["ref_of"];
        if (isset($users[$ref_id]) && $users[$user_id]["confirmed"]) {
            if (!in_array($user_id, $users[$ref_id]['confirmed_refs'] ?? [])) {
                $users[$ref_id]['confirmed_refs'][] = $user_id;
                $users[$ref_id]["diamonds"] += 15;
                                                                                                                                                                                                
                $ref_user_name = $users[$user_id]['username'] ? "@" . $users[$user_id]['username'] : "ID: " . $user_id;
                $referrer_name = $users[$ref_id]['username'] ? "@" . $users[$ref_id]['username'] : "ID: " . $ref_id;
                                                                                                                                                                                                            
                bot_sendMessage($ref_id,
                    "🎉 Tabriklaymiz! Sizning taklif qilgan do'stingiz botdan foydalandi!\n\n" .
                    "Siz taklif qilgan {$ref_user_name} foydalanuvchi botdan foydalandi. Sizga ham +15 olmos berildi ✅\n\n" .
                    "💎 Jami olmoslar: {$users[$ref_id]['diamonds']}");
                                                                                                                                                                                                                                                                
                $confirmed_count = count($users[$ref_id]['confirmed_refs'] ?? []);
                if ($confirmed_count % 5 === 0) {
                    $users[$ref_id]["diamonds"] += 5;
                    $users[$ref_id]["bonus_refs"] = ($users[$ref_id]["bonus_refs"] ?? 0) + 1;
                                                                                                                                                                                                                
                    bot_sendMessage($ref_id,
                        "🎉 Tabriklaymiz! Sizda {$confirmed_count} ta tasdiqlangan referal!\n" .
                        "💎 Bonus: +5 olmos\n" .
                        "💎 Jami olmoslar: {$users[$ref_id]['diamonds']}");
                }
            }
        }
    }
                                                                                                                                                                                    
    send_sticker($user_id, 'service_success');
    
    $delivery_time = calculate_delivery_time($order['type'] == 'views' ? $order['views'] : ($order['type'] == 'likes' ? $order['likes_count'] : $order['followers_count']));
    
    $completion_message = "✅ Buyurtma qabul qilindi! ";
    
    if ($order['type'] == 'views') {
        $completion_message .= "Ko'rishlar qo'shilmoqda...\n\n" .
            "🎯 Ko'rishlar soni: " . number_format($order['views']) . " ta\n" .
            "💎 Sarflangan olmos: " . $order['diamonds'] . " ta\n";
    } elseif ($order['type'] == 'likes') {
        $completion_message .= "Likelar qo'shilmoqda...\n\n" .
            "🎯 Like soni: " . number_format($order['likes_count']) . " ta\n" .
            "🪙 Sarflangan tanga: " . $order['coins'] . " ta\n";
    } elseif ($order['type'] == 'shipments') {
        $coins_needed = $order['coins'];
        $current_coins = $users[$user_id]["coins"] ?? 0;
        
        if ($current_coins >= $coins_needed) {
            $users[$user_id]["coins"] -= $coins_needed;
        } else {
            return false;
        }
    }
    
    $completion_message .= $delivery_time;
        
    bot_sendMessage($user_id, $completion_message, null, "HTML");
        
    // order bilan bog'liq foydalanuvchi $user_id deb e'lon qilingan
    save_user($user_id, $users[$user_id]);
    return true;
                                                                                                                                                                                            
    return false;
}

function process_pending_orders() {
    $pending_orders = load_json(PENDING_ORDERS_FILE, []);
    $current_time = time();
    $remaining_orders = [];
    
    foreach ($pending_orders as $pending) {
        if ($current_time >= $pending['send_time']) {
            send_order_completion_notification($pending['order_id']);
        } else {
            $remaining_orders[] = $pending;
        }
    }
    
    save_json(PENDING_ORDERS_FILE, $remaining_orders);
}

function process_pending_channel_orders() {
    $pending_orders = load_json(PENDING_CHANNEL_ORDERS_FILE, []);
    $current_time = time();
    $remaining_orders = [];
    
    foreach ($pending_orders as $pending) {
        if ($current_time >= $pending['send_time']) {
            send_order_completion_notification($pending['order_id']);
        } else {
            $remaining_orders[] = $pending;
        }
    }
    
    save_json(PENDING_CHANNEL_ORDERS_FILE, $remaining_orders);
}

function start_views_boost($order_id) {
    global $orders;
    
    if (isset($orders[$order_id])) {
        $order = $orders[$order_id];
        
        if ($order['type'] == 'views') {
            $result = boost_instagram_views($order['link'], $order['views']);
        } elseif ($order['type'] == 'likes') {
            $result = boost_instagram_likes($order['link'], $order['likes_count']);
        } elseif ($order['type'] == 'followers') {
            $result = boost_instagram_followers($order['link'], $order['followers_count']);
        }
        
        if ($result && isset($result['order'])) {
            $orders[$order_id]['status'] = 'completed';
            $orders[$order_id]['boost_id'] = $result['order'];
            save_json(ORDERS_FILE, $orders);
            
            return true;
        } elseif ($result && isset($result['error'])) {
            bot_sendMessage($order['user_id'], 
                "❌ Xizmatda vaqtinchalik muammo yuz berdi.\n" .
                "📞 Iltimos, birozdan keyin qayta urinib ko'ring yoki admin bilan bog'laning.",
                null, "HTML");
            return false;
        }
    }
    return false;
}

function send_welcome_video($chat_id, $username = "") {
    global $videos, $users;
    $uid = strval($chat_id);
    
    // FAQAT TUGMA MENYUSINI OCHISH
    bot_sendMessage($chat_id, " ", get_reply_keyboard($username));
    
    // VIDEO DARSLIKNI YUBORISH
    if (!empty($videos) && !$users[$uid]['intro_video_sent']) {
        $first_video_key = array_key_first($videos);
        $video_caption = "🎬 " . $videos[$first_video_key]['title'] . "\n\n" . $videos[$first_video_key]['desc'];
        
        if (isset($videos[$first_video_key]['file_id'])) {
            bot_sendVideo($chat_id, $videos[$first_video_key]['file_id'], $video_caption);
        } elseif (isset($videos[$first_video_key]['link'])) {
            $video_kb = [
                "inline_keyboard" => [
                    [["text" => "🎬 Videoni ko'rish", "url" => $videos[$first_video_key]['link']]]
                ]
            ];
            bot_sendMessage($chat_id, $video_caption, $video_kb);
        }
        
        $users[$uid]['intro_video_sent'] = true;
        save_user($uid, $users[$uid]);
        return true;
    }
    
    return false;
}
function show_video_tutorial($chat_id, $username = "") {
    global $videos;
    
    if (empty($videos)) {
        bot_sendMessage($chat_id, "🎬 Video qo'llanmalar hozircha yo'q.", get_reply_keyboard($username));
        return false;
    }
    
    $video_list = [];
    $kb = [];
    $index = 0;
    foreach ($videos as $video_id => $meta) {
        if (is_array($meta)) {
            $video_list[] = $video_id;
            $kb[] = [["text" => $meta['title'], "callback_data" => "v{$index}"]];
        }
        $index++;
    }
    set_user_state($chat_id, "video_list", ['videos' => $video_list]);
    $kb_markup = ["inline_keyboard" => $kb];
    bot_sendMessage($chat_id, "🎬 Video qo'llanmalar ro'yxati:", $kb_markup);
    return true;
}

function add_admin($new_admin) {
    global $config;
    if (!in_array($new_admin, $config['admins'])) {
        $config['admins'][] = $new_admin;
        save_json(CONFIG_FILE, $config);
        return true;
    }
    return false;
}

function create_views_confirmation_kb($views, $diamonds) {
    $delivery_time = calculate_delivery_time($views);
    
    return [
        "inline_keyboard" => [
            [
                ["text" => "✅ Roziman", "callback_data" => "confirm_views|{$views}|{$diamonds}"],
                ["text" => "❌ Bekor qilish", "callback_data" => "cancel_views"]
            ]
        ]
    ];
}

function send_broadcast_with_media($media_type, $media_file_id, $caption, $reply_markup = null) {
    global $users;
    $sent = 0;
    $failed = 0;
    
    foreach ($users as $user_id => $user_data) {
        if (isset($user_data['confirmed']) && $user_data['confirmed']) {
            $result = null;
            
            switch ($media_type) {
                case 'photo':
                    $result = bot_sendPhoto($user_id, $media_file_id, $caption, $reply_markup);
                    break;
                case 'video':
                    $result = bot_sendVideo($user_id, $media_file_id, $caption, $reply_markup);
                    break;
                case 'document':
                    $result = bot_sendDocument($user_id, $media_file_id, $caption, $reply_markup);
                    break;
                default:
                    $result = bot_sendMessage($user_id, $caption, $reply_markup);
                    break;
            }
            
            if ($result && $result['ok']) {
                $sent++;
            } else {
                $failed++;
            }
            
            usleep(100000);
        }
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

function get_reply_keyboard($username = "") {
    global $buttons;
    $is_admin = in_array($username, ADMINS);
    
    // Asosiy tugmalar
    $kb = [
        [["text" => "👁 1k"], ["text" => "👁 5k"], ["text" => "👁 10k"]],
        [["text" => "🔢 Ko'rishlar sonini kiritish"], ["text" => "💎 Olmos yeg'ish"]],
        [["text" => "🛒 Do'kon"], ["text" => "💰 Balans"]],
        [["text" => "🎬 Video qo'llanma"], ["text" => "🎁 Promokod"]],
        [["text" => "👑 Xizmatlar"]]
    ];
    
    // Qo'shimcha tugmalarni qo'shish
    if (!empty($buttons)) {
        foreach ($buttons as $title => $btn) {
            if (isset($btn['position'])) {
                $row = $btn['position'];
                if (!isset($kb[$row])) {
                    $kb[$row] = [];
                }
                $kb[$row][] = ["text" => $title];
            } else {
                $kb[] = [["text" => $title]];
            }
        }
    }
    
    // Qatorlarni tartibga solish
    ksort($kb);
    $kb = array_values($kb);
    
    // Admin tugmalari
    if ($is_admin) {
        $kb[] = [["text" => "📋 Tugmalar ro'yxati"], ["text" => "⚙️ Admin panel"]];
        $kb[] = [["text" => "📊 Statistika"]];
    }
    
    return [
        "keyboard" => $kb,
        "resize_keyboard" => true,
        "one_time_keyboard" => false
    ];
}
function get_shop_keyboard() {
    $kb = [
        [["text" => "💎 50 olmos - 5,000 so'm"], ["text" => "💎 100 olmos - 9,000 so'm"]],
        [["text" => "💎 300 olmos - 25,000 so'm"], ["text" => "💎 Boshqa miqdor"]],
        [["text" => "❌ Bekor qilish"]]
    ];
    return ["keyboard" => $kb, "resize_keyboard" => true];
}

function get_admin_keyboard() {
    $kb = [
        [["text" => "➕ Kanal qo'shish"], ["text" => "❌ Kanal o'chirish"]],
        [["text" => "🆕 Tugma qo'shish"], ["text" => "🗑 Tugma o'chirish"]],
        [["text" => "🎬 Video qo'llanma qo'shish"], ["text" => "🗑 Video o'chirish"]],
        [["text" => "🎁 Promokod qo'shish"], ["text" => "📋 Promokodlar"], ["text" => "🗑 Promokod o'chirish"]],
        [["text" => "➕ Admin qo'shish"], ["text" => "👤 Olmos boshqarish"], ["text" => "🪙 Tanga boshqarish"]],
        [["text" => "👤 Adminga murojaat sozlash"], ["text" => "📢 Reklama tarqatish"]],
        [["text" => "🎁 Bonus kanal qo'shish"], ["text" => "🗑 Bonus kanal o'chirish"]],
        [["text" => "✨ Stiker qo'shish"], ["text" => "🗑 Stiker o'chirish"]],
        [["text" => "📊 Statistika"]],
        [["text" => "⬅️ Ortga"]]
    ];
    return ["keyboard" => $kb, "resize_keyboard" => true];
}

function make_free_diamonds_kb() {
    return [
        "inline_keyboard" => [
            [["text" => "👬 Do'stlarni taklif qilish", "callback_data" => "invite_friends"]],
            [["text" => "🎬 Video qo'llanmani ko'rish", "callback_data" => "watch_tutorial"]]
        ]
    ];
}

function make_insufficient_diamonds_kb() {
    return [
        "inline_keyboard" => [
            [["text" => "💎 Olmos yeg'ish", "callback_data" => "free_diamonds"]],
            [["text" => "🛒 Do'kon", "callback_data" => "open_shop"]]
        ]
    ];
}

function make_insufficient_coins_kb() {
    return [
        "inline_keyboard" => [
            [["text" => "🪙 Tanga sotib olish", "callback_data" => "buy_coins_direct"]],
            [["text" => "🛒 Do'kon", "callback_data" => "open_shop"]]
        ]
    ];
}

function make_balance_kb() {
    return [
        "inline_keyboard" => [
            [["text" => "🛒 Do'kon", "callback_data" => "open_shop"]],
            [["text" => "💎 Olmos yeg'ish", "callback_data" => "free_diamonds"]]
        ]
    ];
}

function get_referrals_info($uid) {
    global $users;
    
    if (!isset($users[$uid]['refs']) || empty($users[$uid]['refs'])) {
        return "Siz hali hech qanday referal taklif qilmagansiz.";
    }
    
    $confirmed_refs = [];
    $pending_refs = [];
    
    foreach ($users[$uid]['refs'] as $ref_id) {
        if (isset($users[$ref_id])) {
            $ref_user = $users[$ref_id];
            $username = $ref_user['username'] ? "@" . $ref_user['username'] : "ID: " . $ref_id;
            
            if ($ref_user['confirmed']) {
                $confirmed_refs[] = $username;
            } else {
                $pending_refs[] = $username . " (obuna bo'lmagan)";
            }
        }
    }
    
    $result = "📊 Referallar statistikasi:\n\n";
    $result .= "✅ Tasdiqlangan referallar: " . count($confirmed_refs) . " ta\n";
    
    if (!empty($confirmed_refs)) {
        $result .= "👥 Ro'yxat:\n";
        foreach ($confirmed_refs as $index => $ref) {
            $result .= ($index + 1) . ". {$ref}\n";
        }
    }
    
    $result .= "\n⏳ Tasdiqlanishi kutilayotgan: " . count($pending_refs) . " ta\n";
    
    if (!empty($pending_refs)) {
        $result .= "👥 Ro'yxat:\n";
        foreach ($pending_refs as $index => $ref) {
            $result .= ($index + 1) . ". {$ref}\n";
        }
    }
    
    $total_refs = count($confirmed_refs) + count($pending_refs);
    $result .= "\n📈 Jami taklif qilganlar: {$total_refs} ta";
    
    return $result;
}

function make_earn_diamonds_kb() {
    $kb = [
        "inline_keyboard" => [
            [["text" => "📤 Do'stlarga ulashish", "callback_data" => "share_invite"]],
            [["text" => "👥 Referallarim", "callback_data" => "my_referrals"]],
            [["text" => "🎬 Video qo'llanmani ko'rish", "callback_data" => "watch_tutorial"]]
        ]
    ];
    
    return $kb;
}

function update_promocode_message($promo_name) {
    global $promocodes, $promo_messages;
    
    if (!isset($promocodes[$promo_name]) || !isset($promo_messages[$promo_name])) {
        return false;
    }
    
    $promo = $promocodes[$promo_name];
    $message_data = $promo_messages[$promo_name];
    $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
    
    $currency_icon = (isset($promo['type']) && $promo['type'] == 'coins') ? '🪙' : '💎';
    $currency_name = (isset($promo['type']) && $promo['type'] == 'coins') ? 'tanga' : 'olmos';
    
    $message = "🎁 PROMOKOD: {$promo_name}\n\n";
    $message .= "{$currency_icon} Mukofot: {$promo['amount']} {$currency_name}\n";
    $message .= "👥 Foydalanilgan: {$used_count} kishi\n";
    
    if ($promo['limit_type'] == 'time_limit') {
        $current_time = time();
        $expires_time = strtotime($promo['expires_at']);
        $time_left = $expires_time - $current_time;
        
        if ($time_left > 0) {
            $hours_left = floor($time_left / 3600);
            $minutes_left = floor(($time_left % 3600) / 60);
            $message .= "⏰ Qolgan vaqt: {$hours_left} soat {$minutes_left} daqiqa\n";
            $message .= "📊 Holat: 🟢 Faol";
        } else {
            $message .= "⏰ Muddati tugagan\n";
            $message .= "📊 Holat: 🔴 Muddati tugagan";
        }
    } else {
        $remaining_uses = $promo['limit'] - $used_count;
        if ($remaining_uses > 0) {
            $message .= "📊 Limit: {$promo['limit']} ta (qolgan: {$remaining_uses})\n";
            $message .= "📊 Holat: 🟢 Faol";
        } else {
            $message .= "📊 Limit: {$promo['limit']} ta (qolgan: 0)\n";
            $message .= "📊 Holat: 🔴 Limit tugagan";
        }
    }
    
    try {
        $result = bot_editMessageText($message_data['chat_id'], $message_data['message_id'], $message);
        return $result && isset($result['ok']) && $result['ok'];
    } catch (Exception $e) {
        error_log("Promokod xabarini yangilashda xatolik: " . $e->getMessage());
        return false;
    }
}

function send_promocode_to_channel($promo_name) {
    global $promocodes, $promo_messages;
    
    if (!isset($promocodes[$promo_name])) {
        error_log("Promokod topilmadi: " . $promo_name);
        return false;
    }
    
    $promo = $promocodes[$promo_name];
    $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
    
    // Valyuta belgilarini aniqlash
    $currency_icon = (isset($promo['type']) && $promo['type'] == 'coins') ? '🪙' : '💎';
    $currency_name = (isset($promo['type']) && $promo['type'] == 'coins') ? 'tanga' : 'olmos';
    
    $message = "🎁 YANGI PROMOKOD! 🎁\n\n";
    $message .= "🔑 KOD: <b>{$promo_name}</b>\n";
    $message .= "{$currency_icon} Mukofot: <b>{$promo['amount']} {$currency_name}</b>\n";
    
    if ($promo['limit_type'] == 'time_limit') {
        $expires_at = isset($promo['expires_at']) ? date('d.m.Y H:i', strtotime($promo['expires_at'])) : 'Cheksiz';
        $current_time = time();
        $expires_time = strtotime($promo['expires_at']);
        $time_left = $expires_time - $current_time;
        
        if ($time_left > 0) {
            $hours_left = floor($time_left / 3600);
            $minutes_left = floor(($time_left % 3600) / 60);
            $message .= "⏰ Qolgan vaqt: <b>{$hours_left} soat {$minutes_left} daqiqa</b>\n";
            $message .= "📊 Holat: 🟢 Faol";
        } else {
            $message .= "⏰ Muddati tugagan\n";
            $message .= "📊 Holat: 🔴 Muddati tugagan";
        }
    } else {
        $remaining_uses = $promo['limit'] - $used_count;
        if ($remaining_uses > 0) {
            $message .= "📊 Limit: <b>{$promo['limit']} ta</b> (qolgan: <b>{$remaining_uses}</b>)\n";
            $message .= "📊 Holat: 🟢 Faol";
        } else {
            $message .= "📊 Limit: <b>{$promo['limit']} ta</b> (qolgan: <b>0</b>)\n";
            $message .= "📊 Holat: 🔴 Limit tugagan";
        }
    }
    
    $message .= "\n\n🤖 Bot: @" . BOT_USERNAME;
    
    // Inline keyboard qo'shish
    $kb = [
        "inline_keyboard" => [
            [["text" => "🚀 Botga o'tish", "url" => "https://t.me/" . BOT_USERNAME]]
        ]
    ];
    
    $result = bot_sendMessage(PROMO_CHANNEL, $message, $kb, "HTML");
    
    if ($result && isset($result['ok']) && $result['ok']) {
        $promo_messages[$promo_name] = [
            'chat_id' => PROMO_CHANNEL,
            'message_id' => $result['result']['message_id'],
            'created_at' => time(),
            'last_used_count' => $used_count
        ];
        save_json(PROMO_MESSAGES_FILE, $promo_messages);
        
        return true;
    }
    
    error_log("Promokodni kanalga yuborishda xatolik: " . json_encode($result));
    return false;
}
function schedule_promocode_updates() {
    global $promocodes, $promo_messages;
    
    $current_time = time();
    $updated_count = 0;
    
    foreach ($promocodes as $promo_name => $promo) {
        if (isset($promo_messages[$promo_name])) {
            $message_data = $promo_messages[$promo_name];
            $last_update = $message_data['created_at'] ?? 0;
            
            if ($promo['limit_type'] == 'time_limit') {
                if ($current_time - $last_update >= 900) {
                    if (update_promocode_message($promo_name)) {
                        $promo_messages[$promo_name]['created_at'] = $current_time;
                        $updated_count++;
                    }
                }
            } else {
                $used_count = isset($promo['used_by']) ? count($promo['used_by']) : 0;
                $last_used_count = $message_data['last_used_count'] ?? 0;
                
                if ($used_count != $last_used_count) {
                    if (update_promocode_message($promo_name)) {
                        $promo_messages[$promo_name]['last_used_count'] = $used_count;
                        $promo_messages[$promo_name]['created_at'] = $current_time;
                        $updated_count++;
                    }
                }
            }
        }
    }
    
    if ($updated_count > 0) {
        save_json(PROMO_MESSAGES_FILE, $promo_messages);
    }
    
    return $updated_count;
}

$input = file_get_contents("php://input");
$update = json_decode($input, true);

if (!$update) {
    // Webhookda background vazifalar bajarmang — ularni cron bilan qayta ishlang
    exit;
}

$message = isset($update['message']) ? $update['message'] : null;
$callback_query = isset($update['callback_query']) ? $update['callback_query'] : null;

if ($message) {
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $username = isset($message['from']['username']) ? $message['from']['username'] : "";
    $text = isset($message['text']) ? $message['text'] : "";
    $uid = strval($user_id);

    // Ensure global users array present
    if (!isset($users) || !is_array($users)) $users = [];

    // Lazy-load user from DB if not in memory
    // START komandasida yangi foydalanuvchi yaratish qismini yangilang:
    // Yangi foydalanuvchi yaratish qismini yangilang:
    if (!isset($users[$uid])) {
        $existing_user = get_user($uid);
        if ($existing_user !== null && $existing_user !== false) {
            $users[$uid] = $existing_user;
        } else {
            // Create new user structure with defaults
            $users[$uid] = [
                "refs" => [],
                "confirmed" => false,
                "hashtags" => [],
                "await_video" => false,
                "views" => 0,
                "username" => $username,
                "diamonds" => 0,
                "coins" => 0,
                "intro_video_sent" => false,
                "bonus_refs" => 0,
                "ref_of" => null,
                "ref_reward_given" => false,
                "video_watched" => false,  // Start bonus berilmagan
                "used_diamonds" => 0,
                "confirmed_refs" => [],
                "ref_initial_notified" => false,
                "ref_confirm_notified" => false,
                "channel_rewards" => [],
                "penalty_warnings" => 0,
                "hashtag_warnings" => 0,
                "like_errors" => 0,
                "follower_errors" => 0
            ];

            // Persist to MySQL
            if (!save_user($uid, $users[$uid])) {
                error_log("Yangi foydalanuvchini MySQL ga saqlashda xatolik: {$uid}");
            } else {
                error_log("Yangi foydalanuvchi MySQL ga saqlandi: {$uid}");
            }
        }
    } else {
        // User already loaded in memory — ensure required fields exist and username up-to-date
        if ($username !== "" && (!isset($users[$uid]['username']) || $users[$uid]['username'] !== $username)) {
            $users[$uid]['username'] = $username;
            // Optionally: save_user($uid, $users[$uid]);
        }

        $required_fields = [
            "refs" => [],
            "confirmed" => false,
            "hashtags" => [],
            "await_video" => false,
            "views" => 0,
            "intro_video_sent" => false,
            "bonus_refs" => 0,
            "ref_of" => null,
            "ref_reward_given" => false,
            "video_watched" => false,
            "used_diamonds" => 0,
            "confirmed_refs" => [],
            "ref_initial_notified" => false,
            "ref_confirm_notified" => false,
            "channel_rewards" => [],
            "penalty_warnings" => 0,
            "hashtag_warnings" => 0,
            "like_errors" => 0,
            "follower_errors" => 0,
            "diamonds" => 0,
            "coins" => 0
        ];
        foreach ($required_fields as $field => $default) {
            if (!isset($users[$uid][$field])) $users[$uid][$field] = $default;
        }
    }
    
    $user_state_data = get_user_state($uid);
    $user_state = $user_state_data ? $user_state_data['state'] : null;
    
    if (strpos($text, '/start') === 0) {
        send_sticker($chat_id, 'start');
        
        $parts = explode(' ', $text);
        $ref_code = count($parts) > 1 ? $parts[1] : null;

        // Referal kodini tekshirish
        if ($ref_code && $ref_code != $uid) {
            if (is_numeric($ref_code) || preg_match('/^[a-zA-Z0-9_]+$/', $ref_code)) {
                $ref_user_id = $ref_code;
                
                if ($ref_user_id != $uid && !isset($users[$uid]["ref_of"])) {
                    $users[$uid]["ref_of"] = $ref_user_id;
                    $users[$uid]["ref_reward_given"] = false;

                    if (isset($users[$ref_user_id])) {
                        if (!in_array($uid, $users[$ref_user_id]["refs"] ?? [])) {
                            if (!isset($users[$ref_user_id]["refs"])) {
                                $users[$ref_user_id]["refs"] = [];
                            }
                            $users[$ref_user_id]["refs"][] = $uid;
                            
                            // Referrer ga darhol xabar yuborish
                            $new_user_name = $username ? "@" . $username : "ID: " . $uid;
                            $referrer_name = isset($users[$ref_user_id]['username']) && $users[$ref_user_id]['username'] ? 
                                "@" . $users[$ref_user_id]['username'] : "ID: " . $ref_user_id;
                            
                            $ref_message = "Siz taklif qilgan {$new_user_name} botga qo'shildi.\n\n" .
                                        "⚠️ Ammo u hali botdagi kanalga obuna bo'lmadi. " .
                                        "Agar kanalga obuna bo'lib tekshirish tugmasini bossa sizga +15 💎 olmos qo'shiladi ✅";
                            
                            bot_sendMessage($ref_user_id, $ref_message);
                            
                            $users[$uid]["ref_initial_notified"] = true;
                        }
                    }
                    
                    // Ma'lumotlarni saqlash
                    persist_user($uid);
                    if (isset($users[$ref_user_id])) {
                        persist_user($ref_user_id);
                    }
                    
                    // **MUHIM: Agar foydalanuvchi allaqachon obuna bo'lgan bo'lsa, DARROV referal mukofotini tekshirish**
                    if (is_user_subscribed_to_required_channels($user_id)) {
                        $users[$uid]["confirmed"] = true;
                        save_user($uid, $users[$uid]);
                        
                        // **FAQAT agar mukofot berilmagan bo'lsa tekshirish**
                        if (!$users[$uid]["ref_reward_given"]) {
                            $reward_result = check_and_reward_referrer($user_id);
                            if ($reward_result) {
                                error_log("START komandasida referal mukofoti berildi (allaqachon obuna): " . $uid);
                            }
                        }
                    }
                }
            }
        }

        // Agar referal orqali kirmagan bo'lsa yoki allaqachon obuna bo'lgan bo'lsa
        if (!isset($users[$uid]["confirmed"])) {
            $users[$uid]["confirmed"] = false;
        }
        
        // 1. DARROV TUGMA MENYUSINI OCHISH
        bot_sendMessage($chat_id, "🤖 RekchiAI botiga xush kelibsiz!", get_reply_keyboard($username));
        
        // 2. VIDEO DARSLIKNI YUBORISH (agar mavjud bo'lsa)
        if (!empty($videos)) {
            $first_video_key = array_key_first($videos);
            $video_data = $videos[$first_video_key];
            
            $video_caption = "🎬 " . $video_data['title'] . "\n\n" . $video_data['desc'];
            
            // Videoni yuborish
            if (isset($video_data['file_id'])) {
                // Telegram serveridagi video
                bot_sendVideo($chat_id, $video_data['file_id'], $video_caption);
            } elseif (isset($video_data['link'])) {
                // Tashqi havola
                $video_kb = [
                    "inline_keyboard" => [
                        [["text" => "🎬 Videoni ko'rish", "url" => $video_data['link']]]
                    ]
                ];
                bot_sendMessage($chat_id, $video_caption, $video_kb);
            } elseif (isset($video_data['file_path'])) {
                // Lokal fayl
                $video_path = DATA_DIR . '/videos/' . $video_data['file_path'];
                if (file_exists($video_path)) {
                    // Lokal videoni yuklash va yuborish
                    send_local_video($chat_id, $video_path, $video_caption);
                }
            }
            
            // Video yuborilganligini belgilash
            $users[$uid]["intro_video_sent"] = true;
        }
        
        // 3. START BONUS BERISH (agar obuna bo'lgan bo'lsa)
        // START komandasida bonus berish qismini yangilang:
        if (!$users[$uid]["video_watched"] && is_user_subscribed_to_required_channels($user_id)) {
            $bonus_diamonds = 12;
            
            // Balansni yangilash funksiyasidan foydalanish
            $new_balance = update_user_balance($uid, 'diamonds', $bonus_diamonds, 'add');
            
            if ($new_balance !== false) {
                $users[$uid]["video_watched"] = true;
                $users[$uid]["diamonds"] = $new_balance; // Global massivni yangilash
                
                bot_sendMessage($chat_id, 
                    "🎉 Tabriklaymiz! Botga xush kelibsiz!\n" .
                    "💎 Start bonus: +{$bonus_diamonds} olmos\n" .
                    "💎 Jami olmoslar: <b>{$new_balance}</b>\n\n" .
                    "⚠️ Start bonus faqat bir marta beriladi!",
                    null, "HTML");
                    
                // **MUHIM: Agar referal orqali kirgan bo'lsa, referal mukofotini tekshirish**
                if (isset($users[$uid]["ref_of"]) && $users[$uid]["ref_of"] && !$users[$uid]["ref_reward_given"]) {
                    $result = check_and_reward_referrer($user_id);
                    if ($result) {
                        error_log("START bonus berishda referal mukofoti berildi: " . $uid);
                    }
                }
            }
        } else {
            // Agar bonus allaqachon berilgan bo'lsa
            if (is_user_subscribed_to_required_channels($user_id)) {
                $current_diamonds = get_user_balance($uid, 'diamonds');
                bot_sendMessage($chat_id, 
                    "🤖 RekchiAI botiga xush kelibsiz!\n\n" .
                    "ℹ️ Siz start bonusni allaqachon olgansiz!\n" .
                    "💎 Jami olmoslar: <b>{$current_diamonds}</b>",
                    null, "HTML");
            } else {
                // Agar obuna bo'lmagan bo'lsa, obuna so'rash
                ensure_subscribed_or_prompt($chat_id, $user_id, $username, null, false, null);
            }
        }
        
        persist_user($uid);
        exit;
    }
    
    if ($text == "❌ Bekor qilish" || $text == "⬅️ Ortga") {
        send_button_sticker($chat_id, $text);
        clear_user_state($uid);
        bot_sendMessage($chat_id, "Bekor qilindi. Asosiy menyu:", get_reply_keyboard($username));
        exit;
    }
    
    if (!$users[$uid]["confirmed"]) {
        if (!is_user_subscribed_to_required_channels($user_id)) {
            ensure_subscribed_or_prompt($chat_id, $user_id, $username, null, false, null);
            exit;
        }
        $users[$uid]["confirmed"] = true;
        save_user($target_uid, $users[$target_uid]);;
    }

    if (!is_user_subscribed_to_required_channels($user_id)) {
        ensure_subscribed_or_prompt($chat_id, $user_id, $username, null, false, null);
        exit;
    }
    
    switch ($user_state) {
        case "waiting_for_views":
            send_button_sticker($chat_id, "🔢 Ko'rishlar sonini kiritish");
            if (is_numeric($text)) {
                $views = intval($text);
                if ($views < 1000) {
                    bot_sendMessage($chat_id, "❌ Minimal ko'rishlar soni: 1000 ta", get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                $diamonds_needed = calculate_diamonds_for_views($views);
                if ($diamonds_needed == -1) {
                    bot_sendMessage($chat_id, "❌ Noto'g'ri ko'rish miqdori!", get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                // Yangi balans tekshirish funksiyasidan foydalanish
                if (!has_sufficient_balance($uid, 'diamonds', $diamonds_needed)) {
                    $current_diamonds = get_user_balance($uid, 'diamonds');
                    bot_sendMessage($chat_id, 
                        "❌ Sizda yetarli olmos yo'q!\n" .
                        "💎 Sizda: {$current_diamonds} olmos\n" .
                        "💎 Kerak: {$diamonds_needed} olmos\n\n" .
                        "Iltimos, balansingizni to'ldiring yoki olmos yeg'ish tugmasidan foydalaning.",
                        make_insufficient_diamonds_kb());
                    clear_user_state($uid);
                } else {
                    $delivery_time = calculate_delivery_time($views);
                    bot_sendMessage($chat_id,
                        "📊 Ko'rishlar xizmati:\n" .
                        "🎯 Ko'rishlar: " . number_format($views) . " ta\n" .
                        "💎 Narxi: {$diamonds_needed} olmos\n" .
                        $delivery_time . "\n\n" .
                        "Siz bunga rozimisiz?",
                        create_views_confirmation_kb($views, $diamonds_needed));
                    set_user_state($uid, "waiting_views_confirmation", [
                        'views' => $views,
                        'diamonds' => $diamonds_needed
                    ]);
                }
            } else {
                bot_sendMessage($chat_id, 
                    "🔢 Faqat son kiriting! Masalan: 15000\n" .
                    "❗ Minimal: 1000 ta ko'rish", 
                    get_reply_keyboard($username));
                clear_user_state($uid);
            }
            exit;

        case "waiting_for_video_link":
            if (strpos($text, 'http') === 0) {
                $state_data = get_state_data($uid);
                $views = $state_data['views'];
                $diamonds = $state_data['diamonds'];
                
                // Yangi balans tekshirish funksiyasidan foydalanish
                if (!has_sufficient_balance($uid, 'diamonds', $diamonds)) {
                    $current_diamonds = get_user_balance($uid, 'diamonds');
                    bot_sendMessage($chat_id, 
                        "❌ Sizda yetarli olmos yo'q!\n" .
                        "💎 Sizda: {$current_diamonds} olmos\n" .
                        "💎 Kerak: {$diamonds} olmos",
                        get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                // Balansni yangilash
                $new_balance = update_user_balance($uid, 'diamonds', $diamonds, 'subtract');
                
                if ($new_balance === false) {
                    bot_sendMessage($chat_id, 
                        "❌ Balansni yangilashda xatolik! Iltimos, qayta urinib ko'ring.",
                        get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                // used_diamonds ni yangilash
                $users[$uid]["used_diamonds"] = ($users[$uid]["used_diamonds"] ?? 0) + $diamonds;
                
                $video_title = 'Instagram video';
                
                $order_id = rand(100000, 999999);
                
                $orders[$order_id] = [
                    "user_id" => $uid,
                    "username" => $username,
                    "link" => $text,
                    "video_title" => $video_title,
                    "views" => $views,
                    "diamonds" => $diamonds,
                    "created_at" => date('c'),
                    "status" => "active",
                    "type" => "views",
                    "text_checked" => false,
                    "text_valid" => true,
                    "checked_at" => date('Y-m-d H:i:s'),
                    "checked_by" => "none"
                ];
                save_json(ORDERS_FILE, $orders);
                save_user($uid, $users[$uid]);
                
                // PROOF KANALGA YUBORISH
                send_video_to_channel_by_order($order_id);
                
                $delivery_time = calculate_delivery_time($views);
                
                $boost_result = start_views_boost($order_id);
                
                if ($boost_result) {
                    send_sticker($chat_id, 'service_success');
                    bot_sendMessage($chat_id, 
                        "✅ Video qabul qilindi! Ko'rishlar qo'shilmoqda...\n\n" .
                        "🎬 Video: {$video_title}\n" .
                        "🎯 Ko'rishlar soni: " . number_format($views) . " ta\n" .
                        "💎 Sarflangan olmos: " . $diamonds . " ta\n" .
                        "💎 Qolgan olmoslar: " . $new_balance . " ta\n" .
                        $delivery_time,
                        get_reply_keyboard($username));
                } else {
                    bot_sendMessage($chat_id,
                        "❌ Xizmat vaqtinchalik ishlamayapti. Iltimos, keyinroq urinib ko'ring.",
                        get_reply_keyboard($username));
                }
                
                $users[$uid]["await_video"] = false;
                $users[$uid]["hashtags"] = [];
                $users[$uid]["views"] = 0;
                save_user($uid, $users[$uid]);
                clear_user_state($uid);
                
            } else {
                bot_sendMessage($chat_id, "❗ Faqat video havolasini yuboring!", get_reply_keyboard($username));
                clear_user_state($uid);
            }
            exit;
        case "waiting_for_likes_count":
            send_button_sticker($chat_id, "❤️ Like soni");
            if (is_numeric($text)) {
                $likes_count = intval($text);
                
                if ($likes_count < 50) {
                    bot_sendMessage($chat_id, "😊 50 dan katta son yozing...");
                    if (isset($users[$uid]['like_errors'])) {
                        $users[$uid]['like_errors']++;
                        if ($users[$uid]['like_errors'] >= 2) {
                            bot_sendMessage($chat_id, "❌ Funksiya vaqtincha o'chirildi.", get_reply_keyboard($username));
                            clear_user_state($uid);
                            exit;
                        }
                    } else {
                        $users[$uid]['like_errors'] = 1;
                    }
                    save_user($uid, $users[$uid]);
                } else {
                    $users[$uid]['like_errors'] = 0;
                    $coins_needed = calculate_coins_for_likes($likes_count);

                    $current_coins = $users[$uid]["coins"] ?? 0;
                    if ($current_coins < $coins_needed) {
                        bot_sendMessage($chat_id, 
                            "❌ Sizda yetarli tanga yo'q!\n" .
                            "🪙 Sizda: {$current_coins} tanga\n" .
                            "🪙 Kerak: {$coins_needed} tanga\n\n" .
                            "Iltimos, balansingizni to'ldiring.",
                            make_insufficient_coins_kb()
                        );
                        clear_user_state($uid);
                        exit;
                    }

                    bot_sendMessage($chat_id,
                        "📊 Like xizmati:\n" .
                        "🎯 Likelar: " . number_format($likes_count) . " ta\n" .
                        "🪙 Narxi: {$coins_needed} tanga\n" .
                        calculate_delivery_time($likes_count) . "\n\n" .
                        "Siz bunga rozimisiz?",
                        create_likes_confirmation_kb($likes_count, $coins_needed)
                    );

                    set_user_state($uid, "waiting_likes_confirmation", [
                        'likes_count' => $likes_count,
                        'coins_needed' => $coins_needed
                    ]);
                }
            } else {
                bot_sendMessage($chat_id, "❗ Faqat son kiriting! Masalan: 100");
                clear_user_state($uid);
            }
            exit;

        case "waiting_for_likes_link":
            if (strpos($text, 'http') === 0) {
                $state_data = get_state_data($uid);
                $likes_count = $state_data['likes_count'];
                $coins_needed = $state_data['coins_needed'];

                // Yangi balans tekshirish
                if (!has_sufficient_balance($uid, 'coins', $coins_needed)) {
                    $current_coins = get_user_balance($uid, 'coins');
                    bot_sendMessage($chat_id, 
                        "❌ Sizda yetarli tanga yo'q!\n🪙 Sizda: {$current_coins}\n🪙 Kerak: {$coins_needed}", 
                        make_insufficient_coins_kb());
                    clear_user_state($uid);
                    exit;
                }

                // Balansni yangilash
                $new_balance = update_user_balance($uid, 'coins', $coins_needed, 'subtract');
                
                if ($new_balance === false) {
                    bot_sendMessage($chat_id, 
                        "❌ Balansni yangilashda xatolik! Iltimos, qayta urinib ko'ring.",
                        get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }

                $order_id = rand(100000, 999999);
                $orders[$order_id] = [
                    "user_id" => $uid,
                    "username" => $username,
                    "link" => $text,
                    "likes_count" => $likes_count,
                    "coins" => $coins_needed,
                    "created_at" => date('c'),
                    "status" => "pending",
                    "type" => "likes",
                    "service_id" => LIKES_SERVICE_ID
                ];
                save_json(ORDERS_FILE, $orders);

                $boost_result = boost_instagram_likes($text, $likes_count);

                if ($boost_result && (isset($boost_result['order']) || (isset($boost_result['success']) && $boost_result['success']))) {
                    $boost_order_id = isset($boost_result['order']) ? $boost_result['order'] : (isset($boost_result['id']) ? $boost_result['id'] : null);
                    
                    $orders[$order_id]['status'] = 'completed';
                    if ($boost_order_id) $orders[$order_id]['boost_id'] = $boost_order_id;
                    save_json(ORDERS_FILE, $orders);
                    save_user($uid, $users[$uid]);

                    send_sticker($chat_id, 'service_success');
                    $delivery_time = calculate_delivery_time($likes_count);
                    bot_sendMessage($chat_id, 
                        "✅ Video qabul qilindi! Likelar qo'shilmoqda...\n\n" .
                        "🎯 Like soni: " . number_format($likes_count) . " ta\n" .
                        "🪙 Sarflangan tanga: " . $coins_needed . " ta\n" .
                        "🪙 Qolgan tangalar: " . $new_balance . " ta\n" .
                        $delivery_time,
                        get_reply_keyboard($username));
                } else {
                    $orders[$order_id]['status'] = 'failed';
                    $orders[$order_id]['error'] = isset($boost_result['error']) ? $boost_result['error'] : 'NEOSMM error';
                    save_json(ORDERS_FILE, $orders);

                    bot_sendMessage($chat_id,
                        "❌ Xizmatni boshlashda muammo yuz berdi. Iltimos, birozdan keyin qayta urinib ko'ring yoki admin bilan bog'laning.",
                        get_reply_keyboard($username));
                }

                clear_user_state($uid);
            } else {
                bot_sendMessage($chat_id, "❗ Faqat video havolasini yuboring!");
            }
            exit;

        // "waiting_for_followers_count" va "waiting_for_followers_username" state'larini O'CHIRIB, 
        // ularning o'rniga quyidagi yangi state'larni qo'ying:

        // waiting_for_shipments_count ni waiting_for_shipments ga o'zgartiring
        // waiting_for_likes_link case'dan KEYIN qo'shing
        case "waiting_for_shipments_count":
            send_button_sticker($chat_id, "🚀 Jo'natishlar soni");
            if (is_numeric($text)) {
                $shipments_count = intval($text);
                
                if ($shipments_count < 100) {
                    bot_sendMessage($chat_id, "😊 100 dan katta son yozing...");
                    if (isset($users[$uid]['shipment_errors'])) {
                        $users[$uid]['shipment_errors']++;
                        if ($users[$uid]['shipment_errors'] >= 2) {
                            bot_sendMessage($chat_id, "❌ Funksiya vaqtincha o'chirildi.", get_reply_keyboard($username));
                            clear_user_state($uid);
                            exit;
                        }
                    } else {
                        $users[$uid]['shipment_errors'] = 1;
                    }
                    save_user($uid, $users[$uid]);
                    
                    // XABARNI QAYTA YUBORISH - BU YERDA MUHIM!
                    bot_sendMessage($chat_id, 
                        "🚀 Jo'natishlar xizmati\n\n" .
                        "Jo'natishlar sonini kiriting (masalan: 1000):\n" .
                        "Minimal: 100 ta", 
                        get_reply_keyboard($username));
                        
                } else {
                    $users[$uid]['shipment_errors'] = 0;
                    $coins_needed = calculate_coins_for_shipments($shipments_count);

                    $current_coins = get_user_balance($uid, 'coins');
                    if ($current_coins < $coins_needed) {
                        bot_sendMessage($chat_id, 
                            "❌ Sizda yetarli tanga yo'q!\n" .
                            "🪙 Sizda: {$current_coins} tanga\n" .
                            "🪙 Kerak: {$coins_needed} tanga\n\n" .
                            "Iltimos, balansingizni to'ldiring.",
                            make_insufficient_coins_kb()
                        );
                        clear_user_state($uid);
                        exit;
                    }

                    bot_sendMessage($chat_id,
                        "📊 Jo'natishlar xizmati:\n" .
                        "🎯 Jo'natishlar: " . number_format($shipments_count) . " ta\n" .
                        "🪙 Narxi: {$coins_needed} tanga\n" .
                        calculate_delivery_time($shipments_count) . "\n\n" .
                        "Siz bunga rozimisiz?",
                        create_shipments_confirmation_kb($shipments_count, $coins_needed)
                    );

                    set_user_state($uid, "waiting_shipments_confirmation", [
                        'shipments_count' => $shipments_count,
                        'coins_needed' => $coins_needed
                    ]);
                }
            } else {
                bot_sendMessage($chat_id, "❗ Faqat son kiriting! Masalan: 1000");
                
                // XABARNI QAYTA YUBORISH
                bot_sendMessage($chat_id, 
                    "🚀 Jo'natishlar xizmati\n\n" .
                    "Jo'natishlar sonini kiriting (masalan: 1000):\n" .
                    "Minimal: 100 ta", 
                    get_reply_keyboard($username));
            }
            exit;
        case "waiting_for_shipments_link":
            if (strpos($text, 'http') === 0) {
                $state_data = get_state_data($uid);
                $shipments_count = $state_data['shipments_count'];
                $coins_needed = $state_data['coins_needed'];

                // Yangi balans tekshirish
                if (!has_sufficient_balance($uid, 'coins', $coins_needed)) {
                    $current_coins = get_user_balance($uid, 'coins');
                    bot_sendMessage($chat_id, 
                        "❌ Sizda yetarli tanga yo'q!\n🪙 Sizda: {$current_coins}\n🪙 Kerak: {$coins_needed}", 
                        make_insufficient_coins_kb());
                    clear_user_state($uid);
                    exit;
                }

                // Balansni yangilash
                $new_balance = update_user_balance($uid, 'coins', $coins_needed, 'subtract');
                
                if ($new_balance === false) {
                    bot_sendMessage($chat_id, 
                        "❌ Balansni yangilashda xatolik! Iltimos, qayta urinib ko'ring.",
                        get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }

                $order_id = rand(100000, 999999);
                $orders[$order_id] = [
                    "user_id" => $uid,
                    "username" => $username,
                    "link" => $text,
                    "shipments_count" => $shipments_count,
                    "coins" => $coins_needed,
                    "created_at" => date('c'),
                    "status" => "pending",
                    "type" => "shipments",
                    "service_id" => SHIPMENTS_SERVICE_ID
                ];
                save_json(ORDERS_FILE, $orders);

                $boost_result = boost_instagram_shipments($text, $shipments_count);

                if ($boost_result && (isset($boost_result['order']) || (isset($boost_result['success']) && $boost_result['success']))) {
                    $boost_order_id = isset($boost_result['order']) ? $boost_result['order'] : (isset($boost_result['id']) ? $boost_result['id'] : null);
                    
                    $orders[$order_id]['status'] = 'completed';
                    if ($boost_order_id) $orders[$order_id]['boost_id'] = $boost_order_id;
                    save_json(ORDERS_FILE, $orders);
                    save_user($uid, $users[$uid]);

                    send_sticker($chat_id, 'service_success');
                    $delivery_time = calculate_delivery_time($shipments_count);
                    bot_sendMessage($chat_id, 
                        "✅ Video qabul qilindi! Jo'natishlar qo'shilmoqda...\n\n" .
                        "🎯 Jo'natishlar soni: " . number_format($shipments_count) . " ta\n" .
                        "🪙 Sarflangan tanga: " . $coins_needed . " ta\n" .
                        "🪙 Qolgan tangalar: " . $new_balance . " ta\n" .
                        $delivery_time,
                        get_reply_keyboard($username));
                } else {
                    $orders[$order_id]['status'] = 'failed';
                    $orders[$order_id]['error'] = isset($boost_result['error']) ? $boost_result['error'] : 'NEOSMM error';
                    save_json(ORDERS_FILE, $orders);

                    bot_sendMessage($chat_id,
                        "❌ Xizmatni boshlashda muammo yuz berdi. Iltimos, birozdan keyin qayta urinib ko'ring yoki admin bilan bog'laning.",
                        get_reply_keyboard($username));
                }

                clear_user_state($uid);
            } else {
                bot_sendMessage($chat_id, "❗ Faqat video havolasini yuboring!");
            }
            exit;
        // callback_query qismida, boshqa service case'laridan keyin qo'shing
        
        case "waiting_for_promocode":
            $code = strtoupper(trim($text));
            if (isset($promocodes[$code])) {
                $pc = $promocodes[$code];
                $used_by = isset($pc['used_by']) ? $pc['used_by'] : [];
                
                if (isset($pc['expires_at']) && time() > strtotime($pc['expires_at'])) {
                    bot_sendMessage($chat_id, "❌ Ushbu promokodning muddati tugagan!", get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                if (in_array($uid, $used_by)) {
                    bot_sendMessage($chat_id, "❌ Siz bu promokoddan allaqachon foydalangansiz!", get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                if (count($used_by) >= $pc['limit']) {
                    bot_sendMessage($chat_id, "❌ Ushbu promokodning foydalanuvchi limiti tugagan!", get_reply_keyboard($username));
                    clear_user_state($uid);
                    exit;
                }
                
                $give_amount = $pc['amount'];
                
                if (isset($pc['type']) && $pc['type'] == 'coins') {
                    $new_balance = update_user_balance($uid, 'coins', $give_amount, 'add');
                    $currency = "tanga";
                    $current_balance = $new_balance;
                } else {
                    $new_balance = update_user_balance($uid, 'diamonds', $give_amount, 'add');
                    $currency = "olmos";
                    $current_balance = $new_balance;
                }
                
                if ($new_balance !== false) {
                    $used_by[] = $uid;
                    $promocodes[$code]['used_by'] = $used_by;
                    
                    // Global massivni yangilash
                    if (isset($pc['type']) && $pc['type'] == 'coins') {
                        $users[$uid]["coins"] = $new_balance;
                    } else {
                        $users[$uid]["diamonds"] = $new_balance;
                    }
                    
                    save_json(PROMOCODES_FILE, $promocodes);
                    save_user($uid, $users[$uid]);
                    
                    update_promocode_message($code);
                    
                    send_sticker($chat_id, 'promocode_correct');
                    
                    bot_sendMessage($chat_id, 
                        "🎉 Siz <b>{$code}</b> promokodidan foydalandingiz!\n" .
                        "✅ {$give_amount} {$currency} berildi.\n" .
                        ($currency == "olmos" ? "💎" : "🪙") . " Jami {$currency}: <b>{$current_balance}</b>",
                        get_reply_keyboard($username), "HTML");
                } else {
                    bot_sendMessage($chat_id, 
                        "❌ Balans yangilashda xatolik! Iltimos, qayta urinib ko'ring.",
                        get_reply_keyboard($username));
                }
                clear_user_state($uid);
                
            } else {
                bot_sendMessage($chat_id, "❌ Bunday promokod mavjud emas!", get_reply_keyboard($username));
                clear_user_state($uid);
            }
            exit;
            
        case "waiting_for_custom_diamonds":
            send_button_sticker($chat_id, "💎 Boshqa miqdor");
            if (is_numeric($text)) {
                $amount = intval($text);
                if ($amount < 10) {
                    bot_sendMessage($chat_id, "💎 Minimal miqdor: 10 olmos!");
                } else {
                    $price = intval($amount * 100);
                    set_user_state($uid, "waiting_for_diamond_receipt", ['amount' => $amount, 'price' => $price]);
                    $kb = [
                        "inline_keyboard" => [
                            [["text" => "📋 Kartani nusxalash", "callback_data" => "copy_card"]],
                            [["text" => "💎 Olmoslar xarid qilish", "url" => "https://t.me/" . ADMINS[0]]],
                            [["text" => "❌ Bekor qilish", "callback_data" => "cancel_topup"]]
                        ]
                    ];
                    
                    bot_sendMessage($chat_id, 
                        "💎 Olmos xarid qilish:\n" .
                        "🛒 Miqdor: <b>{$amount} olmos</b>\n" .
                        "💰 Narxi: <b>" . number_format($price) . " so'm</b>\n\n" .
                        "💳 Pulni quyidagi karta raqamiga o'tkazing:\n<b>" . TOPUP_CARD_NUMBER . "</b>\n\n" .
                        "To'lov chekini (skrinshot / foto / PDF) pastdan yuboring.",
                        $kb, "HTML");
                }
            } else {
                bot_sendMessage($chat_id, "💎 Iltimos, faqat son kiriting! Masalan: 50");
            }
            exit;

        case "waiting_for_custom_coins":
            send_button_sticker($chat_id, "🪙 Boshqa miqdor");
            if (is_numeric($text)) {
                $amount = intval($text);
                if ($amount < 10) {
                    bot_sendMessage($chat_id, "🪙 Minimal miqdor: 10 tanga!");
                } else {
                    $price = intval($amount * 100);
                    set_user_state($uid, "waiting_for_coin_receipt", ['amount' => $amount, 'price' => $price]);
                    $kb = [
                        "inline_keyboard" => [
                            [["text" => "📋 Kartani nusxalash", "callback_data" => "copy_card"]],
                            [["text" => "🪙 Tanga xarid qilish", "url" => "https://t.me/" . ADMINS[0]]],
                            [["text" => "❌ Bekor qilish", "callback_data" => "cancel_topup"]]
                        ]
                    ];
                    
                    bot_sendMessage($chat_id, 
                        "🪙 Tanga xarid qilish:\n" .
                        "🛒 Miqdor: <b>{$amount} tanga</b>\n" .
                        "💰 Narxi: <b>" . number_format($price) . " so'm</b>\n\n" .
                        "💳 Pulni quyidagi karta raqamiga o'tkazing:\n<b>" . TOPUP_CARD_NUMBER . "</b>\n\n" .
                        "To'lov chekini (skrinshot / foto / PDF) pastdan yuboring.",
                        $kb, "HTML");
                }
            } else {
                bot_sendMessage($chat_id, "🪙 Iltimos, faqat son kiriting! Masalan: 250");
            }
            exit;
            
        case "waiting_for_diamond_receipt":
            $state_data = get_state_data($uid);
            $amount = $state_data['amount'];
            $price = $state_data['price'];
            
            if ($amount === null) {
                bot_sendMessage($chat_id, "❗ Miqdor topilmadi. Iltimos, qaytadan boshlang.", get_reply_keyboard($username));
                clear_user_state($uid);
                exit;
            }

            $caption = "💎 Olmos xaridi\n" .
                       "Foydalanuvchi: @" . ($username ?: $uid) . "\n" .
                       "ID: {$uid}\n" .
                       "Olmoslar: {$amount}\n" .
                       "Summasi: " . number_format($price) . " so'm\n" .
                       "Admin: tasdiqlang yoki bekor qiling.";

            $kb = [
                "inline_keyboard" => [
                    [
                        ["text" => "✅ Tasdiqlash", "callback_data" => "approve_diamonds|{$uid}|{$amount}|{$price}"],
                        ["text" => "❌ Bekor qilish", "callback_data" => "reject_diamonds|{$uid}|{$amount}|{$price}"]
                    ]
                ]
            ];

            $sent = false;
            
            if (isset($message['photo'])) {
                $photo = $message['photo'][count($message['photo'])-1]['file_id'];
                $result = bot_sendPhoto(RECEIPT_CHANNEL, $photo, $caption, $kb);
                $sent = $result && $result['ok'];
            } elseif (isset($message['document'])) {
                $document = $message['document']['file_id'];
                $result = bot_sendDocument(RECEIPT_CHANNEL, $document, $caption, $kb);
                $sent = $result && $result['ok'];
            } elseif ($text) {
                $result = bot_sendMessage(RECEIPT_CHANNEL, $caption . "\n\nFoydalanuvchi xabari:\n" . $text, $kb);
                $sent = $result && $result['ok'];
            }

            if ($sent) {
                bot_sendMessage($chat_id, "✅ Chek yuborildi! Administratsiya tekshiradi. Sizga xabar keladi.", get_reply_keyboard($username));
            } else {
                bot_sendMessage($chat_id, "❗ Chekni yuborishda xatolik yuz berdi. Iltimos, admin bilan bog'laning.", get_reply_keyboard($username));
            }
            
            clear_user_state($uid);
            exit;

        case "waiting_for_coin_receipt":
            $state_data = get_state_data($uid);
            $amount = $state_data['amount'];
            $price = $state_data['price'];
            
            if ($amount === null) {
                bot_sendMessage($chat_id, "❗ Miqdor topilmadi. Iltimos, qaytadan boshlang.", get_reply_keyboard($username));
                clear_user_state($uid);
                exit;
            }

            $caption = "🪙 Tanga xaridi\n" .
                       "Foydalanuvchi: @" . ($username ?: $uid) . "\n" .
                       "ID: {$uid}\n" .
                       "Tanga: {$amount}\n" .
                       "Summasi: " . number_format($price) . " so'm\n" .
                       "Admin: tasdiqlang yoki bekor qiling.";

            $kb = [
                "inline_keyboard" => [
                    [
                        ["text" => "✅ Tasdiqlash", "callback_data" => "approve_coins|{$uid}|{$amount}|{$price}"],
                        ["text" => "❌ Bekor qilish", "callback_data" => "reject_coins|{$uid}|{$amount}|{$price}"]
                    ]
                ]
            ];

            $sent = false;
            
            if (isset($message['photo'])) {
                $photo = $message['photo'][count($message['photo'])-1]['file_id'];
                $result = bot_sendPhoto(RECEIPT_CHANNEL, $photo, $caption, $kb);
                $sent = $result && $result['ok'];
            } elseif (isset($message['document'])) {
                $document = $message['document']['file_id'];
                $result = bot_sendDocument(RECEIPT_CHANNEL, $document, $caption, $kb);
                $sent = $result && $result['ok'];
            } elseif ($text) {
                $result = bot_sendMessage(RECEIPT_CHANNEL, $caption . "\n\nFoydalanuvchi xabari:\n" . $text, $kb);
                $sent = $result && $result['ok'];
            }

            if ($sent) {
                bot_sendMessage($chat_id, "✅ Chek yuborildi! Administratsiya tekshiradi. Sizga xabar keladi.", get_reply_keyboard($username));
            } else {
                bot_sendMessage($chat_id, "❗ Chekni yuborishda xatolik yuz berdi. Iltimos, admin bilan bog'laning.", get_reply_keyboard($username));
            }
            
            clear_user_state($uid);
            exit;
            
        case "admin_await_broadcast_type":
            if (in_array($username, ADMINS)) {
                if ($text == "📝 Faqat matn") {
                    bot_sendMessage($chat_id, "Reklama xabarini kiriting:");
                    set_user_state($uid, "admin_await_broadcast");
                } elseif ($text == "🖼 Rasm bilan") {
                    bot_sendMessage($chat_id, "Rasmni yuboring:");
                    set_user_state($uid, "admin_await_broadcast_photo");
                } elseif ($text == "🎬 Video bilan") {
                    bot_sendMessage($chat_id, "Videoni yuboring:");
                    set_user_state($uid, "admin_await_broadcast_video");
                } elseif ($text == "📎 Fayl bilan") {
                    bot_sendMessage($chat_id, "Faylni yuboring:");
                    set_user_state($uid, "admin_await_broadcast_document");
                } else {
                    bot_sendMessage($chat_id, "Reklama bekor qilindi.", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_broadcast_photo":
            if (in_array($username, ADMINS)) {
                if (isset($message['photo'])) {
                    $photo = $message['photo'][count($message['photo'])-1]['file_id'];
                    set_user_state($uid, "admin_await_broadcast_media", [
                        'media_type' => 'photo',
                        'media_file_id' => $photo
                    ]);
                    bot_sendMessage($chat_id, "Endi reklama matnini kiriting:");
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, rasm yuboring!", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_broadcast_video":
            if (in_array($username, ADMINS)) {
                if (isset($message['video'])) {
                    $video = $message['video']['file_id'];
                    set_user_state($uid, "admin_await_broadcast_media", [
                        'media_type' => 'video',
                        'media_file_id' => $video
                    ]);
                    bot_sendMessage($chat_id, "Endi reklama matnini kiriting:");
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, video yuboring!", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_broadcast_document":
            if (in_array($username, ADMINS)) {
                if (isset($message['document'])) {
                    $document = $message['document']['file_id'];
                    set_user_state($uid, "admin_await_broadcast_media", [
                        'media_type' => 'document',
                        'media_file_id' => $document
                    ]);
                    bot_sendMessage($chat_id, "Endi reklama matnini kiriting:");
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, fayl yuboring!", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_broadcast_media":
            if (in_array($username, ADMINS)) {
                $broadcast_text = $text;
                $media_type = get_state_data($uid, 'media_type');
                $media_file_id = get_state_data($uid, 'media_file_id');
                
                $result = send_broadcast_with_media($media_type, $media_file_id, $broadcast_text);
                
                bot_sendMessage($chat_id, 
                    "✅ Reklama tarqatildi!\nYuborildi: {$result['sent']}\nXato: {$result['failed']}", 
                    get_admin_keyboard());
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_broadcast":
            if (in_array($username, ADMINS)) {
                $broadcast_text = $text;
                $sent = 0;
                $failed = 0;
                
                foreach ($users as $user_id_k => $user_data) {
                    if (isset($user_data['confirmed']) && $user_data['confirmed']) {
                        $result = bot_sendMessage($user_id_k, $broadcast_text);
                        if ($result && $result['ok']) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                        usleep(100000);
                    }
                }
                
                bot_sendMessage($chat_id, "✅ Reklama tarqatildi!\nYuborildi: {$sent}\nXato: {$failed}", get_admin_keyboard());
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_channel":
            if (in_array($username, ADMINS)) {
                $channel = trim($text);
                if ($channel) {
                    if (!in_array($channel, $config['channels'])) {
                        $config['channels'] = array_merge($config['channels'], [$channel]);
                        save_json(CONFIG_FILE, $config);
                        bot_sendMessage($chat_id, "✅ Kanal qo'shildi: {$channel}", get_admin_keyboard());
                    } else {
                        bot_sendMessage($chat_id, "❗ Bu kanal allaqachon ro'yxatda bor.", get_admin_keyboard());
                    }
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_remove_channel":
            if (in_array($username, ADMINS)) {
                $channel = trim($text);
                if (in_array($channel, $config['channels'])) {
                    $config['channels'] = array_values(array_diff($config['channels'], [$channel]));
                    save_json(CONFIG_FILE, $config);
                    bot_sendMessage($chat_id, "✅ Kanal o'chirildi: {$channel}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Kanal topilmadi.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_button_title":
            if (in_array($username, ADMINS)) {
                $title = trim($text);
                if (isset($buttons[$title])) {
                    bot_sendMessage($chat_id, "❗ Bu tugma allaqachon mavjud.", get_admin_keyboard());
                    clear_user_state($uid);
                } else {
                    set_user_state($uid, "admin_await_button_msg", ['title' => $title]);
                    bot_sendMessage($chat_id, "Tugma uchun xabarni kiriting:");
                }
            }
            exit;
            
        case "admin_await_button_msg":
            if (in_array($username, ADMINS)) {
                $title = get_state_data($uid, 'title');
                $msg_text = trim($text);
                if ($title) {
                    set_user_state($uid, "admin_await_button_position", ['title' => $title, 'msg' => $msg_text]);
                    bot_sendMessage($chat_id, "Tugma joylashuvini kiriting (qator raqami, 0 dan boshlanadi):");
                }
            }
            exit;
            
        case "admin_await_button_position":
            if (in_array($username, ADMINS)) {
                $title = get_state_data($uid, 'title');
                $msg_text = get_state_data($uid, 'msg');
                $position = intval(trim($text));
                
                if ($title) {
                    $buttons[$title] = ["msg" => $msg_text, "position" => $position];
                    save_json(BUTTONS_FILE, $buttons);
                    bot_sendMessage($chat_id, "✅ Tugma qo'shildi: {$title} (qator: {$position})", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_remove_button":
            if (in_array($username, ADMINS)) {
                $title = trim($text);
                if (isset($buttons[$title])) {
                    unset($buttons[$title]);
                    save_json(BUTTONS_FILE, $buttons);
                    bot_sendMessage($chat_id, "✅ Tugma o'chirildi: {$title}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Tugma topilmadi.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_video_title":
            if (in_array($username, ADMINS)) {
                $title = trim($text);
                set_user_state($uid, "admin_await_video_desc", ['title' => $title]);
                bot_sendMessage($chat_id, "Video tavsifini kiriting:");
            }
            exit;

        case "admin_await_video_desc":
            if (in_array($username, ADMINS)) {
                $title = get_state_data($uid, 'title');
                $desc = trim($text);
                set_user_state($uid, "admin_await_video_file", ['title' => $title, 'desc' => $desc]);
                bot_sendMessage($chat_id, 
                    "📹 Endi video faylini yuboring (MP4 formatida):");
            }
            exit;

        case "admin_await_video_file":
            if (in_array($username, ADMINS)) {
                if (isset($message['video'])) {
                    $title = get_state_data($uid, 'title');
                    $desc = get_state_data($uid, 'desc');
                    $video_file_id = $message['video']['file_id'];
                    
                    $video_id = uniqid();
                    $videos[$video_id] = [
                        'title' => $title,
                        'desc' => $desc,
                        'file_id' => $video_file_id,
                        'added_by' => $username,
                        'added_at' => date('Y-m-d H:i:s')
                    ];
                    save_json(VIDEOS_META, $videos);
                    
                    bot_sendMessage($chat_id, 
                        "✅ Video qo'llanma qo'shildi!\n\n" .
                        "📹 Sarlavha: {$title}\n" .
                        "📝 Tavsif: {$desc}", 
                        get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, video faylini yuboring!", get_admin_keyboard());
                }
            }
            clear_user_state($uid);
            exit;
            
        case "admin_await_remove_video":
            if (in_array($username, ADMINS)) {
                $title = trim($text);
                $found = null;
                foreach ($videos as $vid_id => $meta) {
                    if (is_array($meta) && isset($meta['title']) && $meta['title'] == $title) {
                        $found = $vid_id;
                        break;
                    }
                }
                if ($found) {
                    unset($videos[$found]);
                    save_json(VIDEOS_META, $videos);
                    bot_sendMessage($chat_id, "✅ Video o'chirildi: {$title}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Video topilmadi.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_remove_promocode":
            if (in_array($username, ADMINS)) {
                $code = strtoupper(trim($text));
                if (isset($promocodes[$code])) {
                    unset($promocodes[$code]);
                    save_json(PROMOCODES_FILE, $promocodes);
                    bot_sendMessage($chat_id, "✅ Promokod o'chirildi: {$code}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Bunday promokod yo'q.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_contact":
            if (in_array($username, ADMINS)) {
                $contact = trim($text);
                if ($contact) {
                    $config['contact'] = $contact;
                    save_json(CONFIG_FILE, $config);
                    bot_sendMessage($chat_id, "✅ Admin kontakt saqlandi: {$contact}", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_promocode_name":
            if (in_array($username, ADMINS)) {
                $name = strtoupper(trim($text));
                if (isset($promocodes[$name])) {
                    bot_sendMessage($chat_id, "❗ Bunday promokod allaqachon mavjud.", get_admin_keyboard());
                    clear_user_state($uid);
                } else {
                    set_user_state($uid, "admin_await_promocode_type", ['name' => $name]);
                    $kb = [
                        "keyboard" => [
                            [["text" => "👥 Foydalanuvchi limiti"]],
                            [["text" => "⏰ Amal qilish muddati"]],
                            [["text" => "❌ Bekor qilish"]]
                        ],
                        "resize_keyboard" => true
                    ];
                    bot_sendMessage($chat_id, "Promokod turini tanlang:", $kb);
                }
            }
            exit;
            
        case "admin_await_promocode_type":
            if (in_array($username, ADMINS)) {
                $type_choice = trim($text);
                $name = get_state_data($uid, 'name');
                
                if ($type_choice == "👥 Foydalanuvchi limiti") {
                    set_user_state($uid, "admin_await_promocode_amount", [
                        'name' => $name,
                        'type' => 'user_limit'
                    ]);
                    bot_sendMessage($chat_id, "Mukofot turini tanlang:", [
                        "keyboard" => [
                            [["text" => "💎 Olmos"]],
                            [["text" => "🪙 Tanga"]],
                            [["text" => "❌ Bekor qilish"]]
                        ],
                        "resize_keyboard" => true
                    ]);
                } elseif ($type_choice == "⏰ Amal qilish muddati") {
                    set_user_state($uid, "admin_await_promocode_amount", [
                        'name' => $name,
                        'type' => 'time_limit'
                    ]);
                    bot_sendMessage($chat_id, "Mukofot turini tanlang:", [
                        "keyboard" => [
                            [["text" => "💎 Olmos"]],
                            [["text" => "🪙 Tanga"]],
                            [["text" => "❌ Bekor qilish"]]
                        ],
                        "resize_keyboard" => true
                    ]);
                } else {
                    bot_sendMessage($chat_id, "Promokod qo'shish bekor qilindi.", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_promocode_amount":
            if (in_array($username, ADMINS)) {
                if ($text == "💎 Olmos" || $text == "🪙 Tanga") {
                    $currency_type = $text == "💎 Olmos" ? 'diamonds' : 'coins';
                    $data = get_state_data($uid);
                    set_user_state($uid, "admin_await_promocode_value", [
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'currency' => $currency_type
                    ]);
                    bot_sendMessage($chat_id, "Mukofot miqdorini kiriting:");
                } else {
                    bot_sendMessage($chat_id, "Promokod qo'shish bekor qilindi.", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;

        case "admin_await_promocode_value":
            if (in_array($username, ADMINS)) {
                if (is_numeric($text)) {
                    $data = get_state_data($uid);
                    if ($data['type'] == 'user_limit') {
                        set_user_state($uid, "admin_await_promocode_limit", [
                            'name' => $data['name'],
                            'type' => $data['type'],
                            'currency' => $data['currency'],
                            'amount' => intval($text)
                        ]);
                        bot_sendMessage($chat_id, "Foydalanish limitini kiriting (nechta odam):");
                    } else {
                        set_user_state($uid, "admin_await_promocode_time", [
                            'name' => $data['name'],
                            'type' => $data['type'],
                            'currency' => $data['currency'],
                            'amount' => intval($text)
                        ]);
                        bot_sendMessage($chat_id, "Amal qilish muddatini kiriting (daqiqalarda):");
                    }
                } else {
                    bot_sendMessage($chat_id, "❗ Faqat son kiriting.");
                }
            }
            exit;
            
        // admin_await_promocode_time va admin_await_promocode_limit qismlarini toping va quyidagicha o'zgartiring:

        case "admin_await_promocode_limit":
            if (in_array($username, ADMINS)) {
                if (is_numeric($text)) {
                    $data = get_state_data($uid);
                    $promocodes[$data['name']] = [
                        "type" => $data['currency'],
                        "limit_type" => $data['type'],
                        "amount" => $data['amount'],
                        "limit" => intval($text),
                        "used_by" => []
                    ];
                    save_json(PROMOCODES_FILE, $promocodes);
                    
                    $currency_icon = $data['currency'] == 'diamonds' ? '💎' : '🪙';
                    $currency_name = $data['currency'] == 'diamonds' ? 'olmos' : 'tanga';

                    // Inline keyboard yaratish
                    $promo_kb = [
                        "inline_keyboard" => [
                            [
                                ["text" => "✅ Kanalga yuborish", "callback_data" => "send_promo_to_channel|{$data['name']}"],
                                ["text" => "❌ Yubormaslik", "callback_data" => "dont_send_promo|{$data['name']}"]
                            ]
                        ]
                    ];

                    bot_sendMessage($chat_id, 
                        "✅ Promokod qo'shildi!\nKod: <b>{$data['name']}</b>\n{$currency_icon} Mukofot: {$data['amount']} {$currency_name}\nLimit: {$data['limit']} ta odam\n\nPromokodni kanalga yuboraymi?",
                        $promo_kb, "HTML"
                    );
                    clear_user_state($uid);

                } else {
                    bot_sendMessage($chat_id, "❗ Faqat son kiriting.");
                }
            }
            exit;
            
        case "admin_await_promocode_time":
            if (in_array($username, ADMINS)) {
                if (is_numeric($text)) {
                    $minutes = intval($text);
                    $data = get_state_data($uid);
                    
                    $timezone = new DateTimeZone('Asia/Tashkent');
                    $now = new DateTime('now', $timezone);
                    $now->modify("+{$minutes} minutes");
                    $expires_at = $now->format('Y-m-d H:i:s');
                    
                    $promocodes[$data['name']] = [
                        "type" => $data['currency'],
                        "limit_type" => $data['type'],
                        "amount" => $data['amount'],
                        "limit" => 1000,
                        "expires_at" => $expires_at,
                        "used_by" => []
                    ];
                    save_json(PROMOCODES_FILE, $promocodes);
                    
                    $expiry_text = $now->format('d.m.Y H:i');
                    $currency_icon = $data['currency'] == 'diamonds' ? '💎' : '🪙';
                    $currency_name = $data['currency'] == 'diamonds' ? 'olmos' : 'tanga';
                    
                    // Kanalga yuborish tugmalari
                    $promo_kb = [
                        "inline_keyboard" => [
                            [
                                ["text" => "✅ Kanalga yuborish", "callback_data" => "send_promo_to_channel|{$data['name']}"],
                                ["text" => "❌ Yubormaslik", "callback_data" => "dont_send_promo|{$data['name']}"]
                            ]
                        ]
                    ];
                    
                    bot_sendMessage($chat_id, 
                        "✅ Promokod qo'shildi!\nKod: <b>{$data['name']}</b>\n{$currency_icon} Mukofot: {$data['amount']} {$currency_name}\nMuddati: {$minutes} daqiqa ({$expiry_text} gacha)\n\nPromokodni kanalga yuboraymi?",
                        $promo_kb, "HTML"
                    );
                    clear_user_state($uid);
                } else {
                    bot_sendMessage($chat_id, "❗ Faqat son kiriting (daqiqada).");
                }
            }
            exit;
            
        case "admin_await_new_admin":
            if (in_array($username, ADMINS)) {
                $new_admin = trim($text);
                if (add_admin($new_admin)) {
                    bot_sendMessage($chat_id, "✅ Yangi admin qo'shildi: {$new_admin}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Bu admin allaqachon mavjud yoki xatolik!", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;

        case "admin_diamond_manage":
            if (in_array($username, ADMINS)) {
                $parts = explode(" ", $text);
                if (count($parts) >= 3) {
                    $action = $parts[0];
                    $target_user = $parts[1];
                    $amount = intval($parts[2]);
                    
                    $target_uid = null;
                    
                    // Foydalanuvchini qidirish
                    // 1. Username bo'yicha ( @ belgisiz )
                    $clean_username = ltrim($target_user, '@');
                    foreach ($users as $uid_k => $user_data) {
                        if (isset($user_data['username']) && 
                            (strcasecmp($user_data['username'], $clean_username) === 0 || 
                            strcasecmp($user_data['username'], $target_user) === 0)) {
                            $target_uid = $uid_k;
                            break;
                        }
                    }
                    
                    // 2. Agar username bo'yicha topilmasa, ID bo'yicha qidirish
                    if (!$target_uid && is_numeric($target_user)) {
                        if (isset($users[$target_user])) {
                            $target_uid = $target_user;
                        } else {
                            // Agar global massivda yo'q bo'lsa, DB dan qidirish
                            $db_user = get_user($target_user);
                            if ($db_user) {
                                $target_uid = $target_user;
                                $users[$target_uid] = $db_user; // Global massivga qo'shish
                            }
                        }
                    }
                    
                    // 3. Agar hali ham topilmasa, barcha foydalanuvchilarni tekshirish
                    if (!$target_uid) {
                        // DB dan barcha foydalanuvchilarni yuklab olish
                        $all_users = load_users();
                        foreach ($all_users as $uid_k => $user_data) {
                            if (isset($user_data['username']) && 
                                (strcasecmp($user_data['username'], $clean_username) === 0 || 
                                strcasecmp($user_data['username'], $target_user) === 0)) {
                                $target_uid = $uid_k;
                                $users[$target_uid] = $user_data; // Global massivga qo'shish
                                break;
                            }
                        }
                    }
                    
                    if ($target_uid && $amount > 0) {
                        // Avval foydalanuvchi ma'lumotlarini yangilab olish
                        refresh_user_data($target_uid);
                        
                        if ($action == 'add') {
                            $new_balance = update_user_balance($target_uid, 'diamonds', $amount, 'add');
                            
                            if ($new_balance !== false) {
                                $msg = "✅ {$amount} olmos qo'shildi";
                                
                                // Foydalanuvchiga xabar yuborish
                                $target_username = isset($users[$target_uid]['username']) ? 
                                    "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                    
                                bot_sendMessage($target_uid,
                                    "🎉 Tabriklaymiz! Admin tomonidan sizga olmos taqdim etildi!\n\n" .
                                    "💎 Miqdor: +{$amount} olmos\n" .
                                    "💎 Jami olmoslar: {$new_balance}");
                                    
                                bot_sendMessage($chat_id, 
                                    "{$msg}\n👤 Foydalanuvchi: {$target_username}\n💎 Jami olmoslar: {$new_balance}",
                                    get_admin_keyboard());
                            } else {
                                bot_sendMessage($chat_id, 
                                    "❌ Olmos qo'shishda xatolik!\n👤 Foydalanuvchi: {$target_user}",
                                    get_admin_keyboard());
                            }
                            
                        } elseif ($action == 'remove') {
                            $current_diamonds = get_user_balance($target_uid, 'diamonds');
                            if ($current_diamonds >= $amount) {
                                $new_balance = update_user_balance($target_uid, 'diamonds', $amount, 'subtract');
                                
                                if ($new_balance !== false) {
                                    $msg = "✅ {$amount} olmos olindi";
                                    
                                    // Foydalanuvchiga xabar yuborish
                                    $target_username = isset($users[$target_uid]['username']) ? 
                                        "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                        
                                    bot_sendMessage($target_uid,
                                        "ℹ️ Sizning hisobingizdan olmoslar olindi\n\n" .
                                        "💎 Miqdor: -{$amount} olmos\n" .
                                        "💎 Jami olmoslar: {$new_balance}");
                                        
                                    bot_sendMessage($chat_id, 
                                        "{$msg}\n👤 Foydalanuvchi: {$target_username}\n💎 Jami olmoslar: {$new_balance}",
                                        get_admin_keyboard());
                                } else {
                                    bot_sendMessage($chat_id, 
                                        "❌ Olmos olib tashlashda xatolik!\n👤 Foydalanuvchi: {$target_user}",
                                        get_admin_keyboard());
                                }
                            } else {
                                $current_diamonds = get_user_balance($target_uid, 'diamonds');
                                $target_username = isset($users[$target_uid]['username']) ? 
                                    "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                    
                                bot_sendMessage($chat_id, 
                                    "❌ Foydalanuvchida yetarli olmos yo'q!\n" .
                                    "👤 Foydalanuvchi: {$target_username}\n" .
                                    "💎 Mavjud: {$current_diamonds}, Kerak: {$amount}",
                                    get_admin_keyboard());
                            }
                        }
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Foydalanuvchi topilmadi yoki miqdor noto'g'ri!\n" .
                            "Qidirilgan: {$target_user}\n" .
                            "Foydalanuvchi ID yoki username ni to'g'ri kiriting.",
                            get_admin_keyboard());
                    }
                } else {
                    bot_sendMessage($chat_id, 
                        "❗ Format: [add/remove] [username/ID] [miqdor]\nMasalan:\n" .
                        "add @username 50\n" .
                        "add 123456789 100\n" .
                        "remove @user 25",
                        get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;

        case "admin_coin_manage":
            if (in_array($username, ADMINS)) {
                $parts = explode(" ", $text);
                if (count($parts) >= 3) {
                    $action = $parts[0];
                    $target_user = $parts[1];
                    $amount = intval($parts[2]);
                    
                    $target_uid = null;
                    
                    // Xuddi shu foydalanuvchi qidirish logikasi
                    $clean_username = ltrim($target_user, '@');
                    foreach ($users as $uid_k => $user_data) {
                        if (isset($user_data['username']) && 
                            (strcasecmp($user_data['username'], $clean_username) === 0 || 
                            strcasecmp($user_data['username'], $target_user) === 0)) {
                            $target_uid = $uid_k;
                            break;
                        }
                    }
                    
                    if (!$target_uid && is_numeric($target_user)) {
                        if (isset($users[$target_user])) {
                            $target_uid = $target_user;
                        } else {
                            $db_user = get_user($target_user);
                            if ($db_user) {
                                $target_uid = $target_user;
                                $users[$target_uid] = $db_user;
                            }
                        }
                    }
                    
                    if (!$target_uid) {
                        $all_users = load_users();
                        foreach ($all_users as $uid_k => $user_data) {
                            if (isset($user_data['username']) && 
                                (strcasecmp($user_data['username'], $clean_username) === 0 || 
                                strcasecmp($user_data['username'], $target_user) === 0)) {
                                $target_uid = $uid_k;
                                $users[$target_uid] = $user_data;
                                break;
                            }
                        }
                    }
                    
                    if ($target_uid && $amount > 0) {
                        // Avval ma'lumotlarni yangilab olish
                        refresh_user_data($target_uid);
                        
                        if ($action == 'add') {
                            $new_balance = update_user_balance($target_uid, 'coins', $amount, 'add');
                            
                            if ($new_balance !== false) {
                                $msg = "✅ {$amount} tanga qo'shildi";
                                
                                $target_username = isset($users[$target_uid]['username']) ? 
                                    "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                    
                                bot_sendMessage($target_uid,
                                    "🎉 Tabriklaymiz! Admin tomonidan sizga tanga taqdim etildi!\n\n" .
                                    "🪙 Miqdor: +{$amount} tanga\n" .
                                    "🪙 Yangi balans: {$new_balance}");
                                    
                                bot_sendMessage($chat_id, 
                                    "{$msg}\n👤 Foydalanuvchi: {$target_username}\n🪙 Jami tangalar: {$new_balance}",
                                    get_admin_keyboard());
                            } else {
                                bot_sendMessage($chat_id, 
                                    "❌ Tanga qo'shishda xatolik!\n👤 Foydalanuvchi: {$target_user}",
                                    get_admin_keyboard());
                            }
                            
                        } elseif ($action == 'remove') {
                            $current_coins = get_user_balance($target_uid, 'coins');
                            if ($current_coins >= $amount) {
                                $new_balance = update_user_balance($target_uid, 'coins', $amount, 'subtract');
                                
                                if ($new_balance !== false) {
                                    $msg = "✅ {$amount} tanga olindi";
                                    
                                    $target_username = isset($users[$target_uid]['username']) ? 
                                        "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                        
                                    bot_sendMessage($target_uid,
                                        "ℹ️ Sizning hisobingizdan tangalar olindi\n\n" .
                                        "🪙 Miqdor: -{$amount} tanga\n" .
                                        "🪙 Yangi balans: {$new_balance}");
                                        
                                    bot_sendMessage($chat_id, 
                                        "{$msg}\n👤 Foydalanuvchi: {$target_username}\n🪙 Jami tangalar: {$new_balance}",
                                        get_admin_keyboard());
                                } else {
                                    bot_sendMessage($chat_id, 
                                        "❌ Tanga olib tashlashda xatolik!\n👤 Foydalanuvchi: {$target_user}",
                                        get_admin_keyboard());
                                }
                            } else {
                                $current_coins = get_user_balance($target_uid, 'coins');
                                $target_username = isset($users[$target_uid]['username']) ? 
                                    "@" . $users[$target_uid]['username'] : "ID: " . $target_uid;
                                    
                                bot_sendMessage($chat_id, 
                                    "❌ Foydalanuvchida yetarli tanga yo'q!\n" .
                                    "👤 Foydalanuvchi: {$target_username}\n" .
                                    "🪙 Mavjud: {$current_coins}, Kerak: {$amount}",
                                    get_admin_keyboard());
                            }
                        }
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Foydalanuvchi topilmadi yoki miqdor noto'g'ri!\n" .
                            "Qidirilgan: {$target_user}",
                            get_admin_keyboard());
                    }
                } else {
                    bot_sendMessage($chat_id, 
                        "❗ Format: [add/remove] [username/ID] [miqdor]\nMasalan:\n" .
                        "add @username 50\n" .
                        "add 123456789 100",
                        get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_bonus_channel_link":
            if (in_array($username, ADMINS)) {
                $channel_link = trim($text);
                if (strpos($channel_link, 'http') === 0) {
                    if (strpos($channel_link, 't.me/') !== false) {
                        $channel_id = '@' . basename($channel_link);
                    } else {
                        $channel_id = $channel_link;
                    }
                    
                    set_user_state($uid, "admin_await_bonus_channel_reward", ['channel_id' => $channel_id]);
                    bot_sendMessage($chat_id, "Bonus kanal uchun mukofot (olmos) miqdorini kiriting:");
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, to'g'ri link kiriting!", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_bonus_channel_reward":
            if (in_array($username, ADMINS)) {
                if (is_numeric($text)) {
                    $reward = intval($text);
                    $data = get_state_data($uid);
                    $channel_id = $data['channel_id'];
                    
                    $channel_ads[$channel_id] = [
                        'channel_id' => $channel_id,
                        'reward' => $reward,
                        'added_by' => $username,
                        'added_at' => date('Y-m-d H:i:s')
                    ];
                    
                    save_json(CHANNEL_ADS_FILE, $channel_ads);
                    
                    send_bonus_channel_notification($channel_id, $reward, $username);
                    
                    bot_sendMessage($chat_id, 
                        "✅ Bonus kanal qo'shildi!\n\n" .
                        "📢 Kanal: {$channel_id}\n" .
                        "💎 Mukofot: {$reward} olmos\n\n" .
                        "📢 E'lon kanaliga xabar yuborildi!\n" .
                        "Endi foydalanuvchilar bu kanalga obuna bo'lib bonus olishlari mumkin!",
                        get_admin_keyboard());
                    
                    clear_user_state($uid);
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, faqat son kiriting!");
                }
            }
            exit;
            
        case "admin_await_remove_bonus_channel":
            if (in_array($username, ADMINS)) {
                $channel_id = trim($text);
                if (isset($channel_ads[$channel_id])) {
                    unset($channel_ads[$channel_id]);
                    save_json(CHANNEL_ADS_FILE, $channel_ads);
                    bot_sendMessage($chat_id, "✅ Bonus kanal o'chirildi: {$channel_id}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Bonus kanal topilmadi.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_sticker_selection":
            if (in_array($username, ADMINS)) {
                $sticker_type = trim($text);
                set_user_state($uid, "admin_await_sticker_file", ['sticker_type' => $sticker_type]);
                bot_sendMessage($chat_id, "Endi stiker faylini yuboring:");
            }
            exit;
            
        case "admin_await_sticker_file":
            if (in_array($username, ADMINS)) {
                if (isset($message['sticker'])) {
                    $sticker_type = get_state_data($uid, 'sticker_type');
                    $sticker_file_id = $message['sticker']['file_id'];
                    
                    $stickers[$sticker_type] = $sticker_file_id;
                    save_json(STICKERS_FILE, $stickers);
                    
                    bot_sendMessage($chat_id, "✅ Stiker qo'shildi: {$sticker_type}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❗ Iltimos, stiker faylini yuboring!", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_remove_sticker":
            if (in_array($username, ADMINS)) {
                $sticker_type = trim($text);
                if (isset($stickers[$sticker_type])) {
                    unset($stickers[$sticker_type]);
                    save_json(STICKERS_FILE, $stickers);
                    bot_sendMessage($chat_id, "✅ Stiker o'chirildi: {$sticker_type}", get_admin_keyboard());
                } else {
                    bot_sendMessage($chat_id, "❌ Stiker topilmadi.", get_admin_keyboard());
                }
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_order_user":
            if (in_array($username, ADMINS)) {
                $user_identifier = trim($text);
                $orders_info = get_user_orders($user_identifier);
                bot_sendMessage($chat_id, $orders_info, get_admin_keyboard());
                clear_user_state($uid);
            }
            exit;
            
        case "admin_await_message_user":
            if (in_array($username, ADMINS)) {
                $user_identifier = trim($text);
                
                $target_user = null;
                foreach ($users as $user_id_k => $user_data) {
                    if ($user_data['username'] == $user_identifier || $user_id_k == $user_identifier) {
                        $target_user = $user_id_k;
                        break;
                    }
                }
                
                if ($target_user) {
                    set_user_state($uid, "admin_await_message_text", ['target_user' => $target_user]);
                    bot_sendMessage($chat_id, "Foydalanuvchiga yubormoqchi bo'lgan xabaringizni kiriting:");
                } else {
                    bot_sendMessage($chat_id, "❌ Foydalanuvchi topilmadi!", get_admin_keyboard());
                    clear_user_state($uid);
                }
            }
            exit;
            
        case "admin_await_message_text":
            if (in_array($username, ADMINS)) {
                $target_user = get_state_data($uid, 'target_user');
                $message_text = $text;
                
                if ($target_user) {
                    $result = bot_sendMessage($target_user, 
                        "📩 ADMIN XABARI:\n\n" . $message_text);
                    
                    if ($result && $result['ok']) {
                        bot_sendMessage($chat_id, 
                            "✅ Xabar foydalanuvchiga muvaffaqiyatli yuborildi!",
                            get_admin_keyboard());
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Xabarni yuborishda xatolik yuz berdi. Foydalanuvchi botni bloklagan bo'lishi mumkin.",
                            get_admin_keyboard());
                    }
                }
                clear_user_state($uid);
            }
            exit;
            
        default:
            switch ($text) {
                case "👁 1k":
                    send_button_sticker($chat_id, $text);
                    $diamonds_needed = calculate_diamonds_for_views(1000);
                    $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                    
                    if ($current_diamonds >= $diamonds_needed) {
                        $delivery_time = calculate_delivery_time(1000);
                        bot_sendMessage($chat_id,
                            "📊 Ko'rishlar xizmati:\n" .
                            "🎯 Ko'rishlar: 1,000 ta\n" .
                            "💎 Narxi: {$diamonds_needed} olmos\n" .
                            $delivery_time . "\n\n" .
                            "Siz bunga rozimisiz?",
                            create_views_confirmation_kb(1000, $diamonds_needed));
                        set_user_state($uid, "waiting_views_confirmation", [
                            'views' => 1000,
                            'diamonds' => $diamonds_needed
                        ]);
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Sizda yetarli olmos yo'q!\n" .
                            "💎 Sizda: {$current_diamonds} olmos\n" .
                            "💎 Kerak: {$diamonds_needed} olmos\n\n" .
                            "Iltimos, balansingizni to'ldiring yoki olmos yeg'ish tugmasidan foydalaning.",
                            make_insufficient_diamonds_kb());
                    }
                    break;
                    
                case "👁 5k":
                    send_button_sticker($chat_id, $text);
                    $diamonds_needed = calculate_diamonds_for_views(5000);
                    $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                    
                    if ($current_diamonds >= $diamonds_needed) {
                        $delivery_time = calculate_delivery_time(5000);
                        bot_sendMessage($chat_id,
                            "📊 Ko'rishlar xizmati:\n" .
                            "🎯 Ko'rishlar: 5,000 ta\n" .
                            "💎 Narxi: {$diamonds_needed} olmos\n" .
                            $delivery_time . "\n\n" .
                            "Siz bunga rozimisiz?",
                            create_views_confirmation_kb(5000, $diamonds_needed));
                        set_user_state($uid, "waiting_views_confirmation", [
                            'views' => 5000,
                            'diamonds' => $diamonds_needed
                        ]);
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Sizda yetarli olmos yo'q!\n" .
                            "💎 Sizda: {$current_diamonds} olmos\n" .
                            "💎 Kerak: {$diamonds_needed} olmos\n\n" .
                            "Iltimos, balansingizni to'ldiring yoki olmos yeg'ish tugmasidan foydalaning.",
                            make_insufficient_diamonds_kb());
                    }
                    break;
                    
                case "👁 10k":
                    send_button_sticker($chat_id, $text);
                    $diamonds_needed = calculate_diamonds_for_views(10000);
                    $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                    
                    if ($current_diamonds >= $diamonds_needed) {
                        $delivery_time = calculate_delivery_time(10000);
                        bot_sendMessage($chat_id,
                            "📊 Ko'rishlar xizmati:\n" .
                            "🎯 Ko'rishlar: 10,000 ta\n" .
                            "💎 Narxi: {$diamonds_needed} olmos\n" .
                            $delivery_time . "\n\n" .
                            "Siz bunga rozimisiz?",
                            create_views_confirmation_kb(10000, $diamonds_needed));
                        set_user_state($uid, "waiting_views_confirmation", [
                            'views' => 10000,
                            'diamonds' => $diamonds_needed
                        ]);
                    } else {
                        bot_sendMessage($chat_id, 
                            "❌ Sizda yetarli olmos yo'q!\n" .
                            "💎 Sizda: {$current_diamonds} olmos\n" .
                            "💎 Kerak: {$diamonds_needed} olmos\n\n" .
                            "Iltimos, balansingizni to'ldiring yoki olmos yeg'ish tugmasidan foydalaning.",
                            make_insufficient_diamonds_kb());
                    }
                    break;
                    
                case "🔢 Ko'rishlar sonini kiritish":
                    send_button_sticker($chat_id, $text);
                    bot_sendMessage($chat_id, "Kerakli ko'rishlar sonini kiriting (masalan: 25000):", get_reply_keyboard($username));
                    set_user_state($uid, "waiting_for_views");
                    break;
                    
                case "🛒 Do'kon":
                    send_button_sticker($chat_id, $text);
                    $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                    $current_coins = $users[$uid]["coins"] ?? 0;
                    
                    $shop_text = "💎 Do'kon\n\n";
                    $shop_text .= "💰 Jami olmoslar: <b>{$current_diamonds}</b>\n";
                    $shop_text .= "🪙 Jami tangalar: <b>{$current_coins}</b>\n\n";
                    $shop_text .= "Quyidagi valyutalardan birini tanlang:";
                    
                    $kb = [
                        "inline_keyboard" => [
                            [["text" => "💎 Olmos sotib olish", "callback_data" => "buy_diamonds"]],
                            [["text" => "🪙 Tanga sotib olish", "callback_data" => "buy_coins"]]
                        ]
                    ];
                    
                    bot_sendMessage($chat_id, $shop_text, $kb, "HTML");
                    break;
                    
                case "💰 Balans":
                    send_button_sticker($chat_id, $text);
                    $current_diamonds = get_user_balance($uid, 'diamonds');
                    $current_coins = get_user_balance($uid, 'coins');
                    $ref_count = get_valid_ref_count($uid);
                        
                    $balance_message = "💎 Jami olmoslar: <b>{$current_diamonds}</b>\n" .
                                    "🪙 Jami tangalar: <b>{$current_coins}</b>\n" .
                                    "👥 Tasdiqlangan referallar: <b>{$ref_count} ta</b>";
                                                        
                    bot_sendMessage($chat_id, $balance_message, make_balance_kb(), "HTML");
                    break;

                case "💎 Olmos yeg'ish":
                    send_button_sticker($chat_id, $text);
                    $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                    $ref_count = get_valid_ref_count($uid);
                    
                    $ref_link = "https://t.me/" . BOT_USERNAME . "?start=" . $uid;
                    
                    $message = "💎 OLMOS YEG'ISH USULLARI 💎\n\n";
                    
                    $message .= "👫 DO'STLARNI TAKLIF QILING\n";
                    $message .= "Har bir do'stingiz uchun +15 💎 olmos oling!\n\n";
                    $message .= "🔗 Sizning referal havolangiz:\n<code>{$ref_link}</code>\n\n";
                    $message .= "📊 Tasdiqlangan referallar: <b>{$ref_count} ta</b>\n";
                    $message .= "💎 Jami olmoslar: <b>{$current_diamonds}</b>";
                    
                    $kb = make_earn_diamonds_kb();
                    
                    bot_sendMessage($chat_id, $message, $kb, "HTML");
                    break;
                    
                case "🎁 Promokod":
                    send_button_sticker($chat_id, $text);
                    bot_sendMessage($chat_id, "🎁 Promokodingizni kiriting:");
                    set_user_state($uid, "waiting_for_promocode");
                    break;
                    
                case "🎬 Video qo'llanma":
                    send_button_sticker($chat_id, $text);
                    show_video_tutorial($chat_id, $username);
                    break;

                // "👑 Xizmatlar" tugmasi uchun kodni toping va yangilang
                // "👑 Xizmatlar" tugmasi uchun keyboardni tekshiring:
                case "👑 Xizmatlar":
                    send_button_sticker($chat_id, $text);
                    $services_text = "👑 XIZMATLAR\n\n";
                    $services_text .= "Quyidagi xizmatlardan birini tanlang:";
                    
                    $kb = [
                        "inline_keyboard" => [
                            [["text" => "❤️ Like", "callback_data" => "service_likes"]],
                            [["text" => "🚀 Jo'natishlar", "callback_data" => "service_shipments"]],
                            [["text" => "⬅️ Ortga", "callback_data" => "main_menu"]]
                        ]
                    ];
                    
                    bot_sendMessage($chat_id, $services_text, $kb);
                    break;
                    
                case "📋 Tugmalar ro'yxati":
                    send_button_sticker($chat_id, $text);
                    $main_buttons = [
                        "👁 1k", "👁 5k", "👁 10k", "🔢 Ko'rishlar sonini kiritish", 
                        "💎 Olmos yeg'ish", "🛒 Do'kon", "💰 Balans", 
                        "🎬 Video qo'llanma", "🎁 Promokod", "👑 Xizmatlar"
                    ];
                    
                    $admin_buttons = [
                        "📊 Statistika", "⚙️ Admin panel", "❌ Bekor qilish", "⬅️ Ortga"
                    ];
                    
                    if (empty($buttons) && empty($main_buttons)) {
                        bot_sendMessage($chat_id, "❗ Tugma ro'yxati bo'sh.", get_reply_keyboard($username));
                    } else {
                        $msg = "📋 Tugmalar ro'yxati:\n\n";
                        $msg .= "🔄 Dasturdagi asosiy tugmalar:\n";
                        foreach ($main_buttons as $btn) {
                            $msg .= "• {$btn}\n";
                        }
                        
                        if (in_array($username, ADMINS)) {
                            $msg .= "\n⚙️ Admin tugmalari:\n";
                            foreach ($admin_buttons as $btn) {
                                $msg .= "• {$btn}\n";
                            }
                        }
                        
                        if (!empty($buttons)) {
                            $msg .= "\n➕ Qo'shimcha tugmalar:\n";
                            foreach ($buttons as $key => $btn) {
                                if (is_array($btn)) {
                                    $position = isset($btn['position']) ? $btn['position'] : 'noma\'lum';
                                    $msg .= "• {$key} (qator: {$position}): {$btn['msg']}\n";
                                }
                            }
                        }
                        bot_sendMessage($chat_id, $msg, get_reply_keyboard($username));
                    }
                    break;
                    
                // --- O'RNIGA JOYLASHTIRING: yangilangan statistika blokini ---
                case "📊 Statistika":
                    send_button_sticker($chat_id, $text);
                    if (in_array($username, ADMINS)) {
                        // DB asosida aniq statistikalar (referallar, tasdiqlangan va jami)
                        $total = db_get_users_count();
                        $total_confirmed = db_count_confirmed_users();
                        $referred_in = db_count_users_with_ref_of(); // foydalanuvchi ref orqali kirganlar
                        $referrers = db_count_referrers(); // ref yig'gan (refs mavjud) foydalanuvchilar soni
                        $total_confirmed_refs = db_sum_confirmed_refs(); // jami tasdiqlangan referallar soni
                        $total_refs = db_sum_total_refs(); // jami taklif qilingan referallar soni (confirmed+pending)
                        
                        // Balanslar, buyurtmalar va promokodlar ham DBga asoslanib olinadi
                        $total_diamonds = db_sum_column('diamonds');
                        $total_coins = db_sum_column('coins');
                        $total_orders = get_total_orders();
                        $total_promo_diamonds = get_total_promocode_diamonds();
                        $total_promo_coins = get_total_promocode_coins();

                        // Buyurtmalar statistikasi (fayl asosida qoladi)
                        $completed_orders = 0;
                        $pending_orders = 0;
                        $failed_orders = 0;
                        foreach ($orders as $order) {
                            if (isset($order['status'])) {
                                if ($order['status'] == 'completed') {
                                    $completed_orders++;
                                } elseif ($order['status'] == 'pending') {
                                    $pending_orders++;
                                } elseif ($order['status'] == 'failed') {
                                    $failed_orders++;
                                }
                            }
                        }

                        // Kanal bonuslari statistikasi
                        $total_channel_bonuses = 0;
                        // Agar $users global bo'lsa hisoblash uchun fallback (qisman saqlanganlar uchun)
                        foreach ($users as $user) {
                            if (isset($user['channel_rewards']) && !empty($user['channel_rewards'])) {
                                $total_channel_bonuses += count($user['channel_rewards']);
                            }
                        }

                        $msg = "📊 <b>Bot statistikasi</b>\n\n" .
                            "👥 <b>Foydalanuvchilar:</b>\n" .
                            "   • Jami: <b>{$total}</b>\n" .
                            "   • Tasdiqlangan (kanallarga obuna): <b>{$total_confirmed}</b>\n" .
                            "   • Referal orqali kirgan: <b>{$referred_in}</b>\n" .
                            "   • Referal yig'gan foydalanuvchilar: <b>{$referrers}</b>\n" .
                            "   • Tasdiqlangan referallar (jami): <b>{$total_confirmed_refs}</b>\n" .
                            "   • Jami taklif qilingan: <b>{$total_refs}</b>\n\n" .

                            "💰 <b>Balanslar:</b>\n" .
                            "   • Jami olmoslar: <b>{$total_diamonds}</b>\n" .
                            "   • Jami tangalar: <b>{$total_coins}</b>\n\n" .

                            "📦 <b>Buyurtmalar:</b>\n" .
                            "   • Jami: <b>{$total_orders}</b>\n" .
                            "   • Bajarilgan: <b>{$completed_orders}</b>\n" .
                            "   • Kutilayotgan: <b>{$pending_orders}</b>\n" .
                            "   • Xatolik: <b>{$failed_orders}</b>\n\n" .

                            "🎁 <b>Promokodlar:</b>\n" .
                            "   • Berilgan olmos: <b>{$total_promo_diamonds}</b>\n" .
                            "   • Berilgan tanga: <b>{$total_promo_coins}</b>\n\n" .

                            "📢 <b>Kanal bonuslari:</b>\n" .
                            "   • Jami berilgan: <b>{$total_channel_bonuses}</b>";

                        bot_sendMessage($chat_id, $msg, make_statistics_kb(), "HTML");
                    } else {
                        bot_sendMessage($chat_id, "Siz admin emassiz!", get_reply_keyboard($username));
                    }
                    break;
                    
                case "⚙️ Admin panel":
                    send_button_sticker($chat_id, $text);
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "⚙️ Admin panel", get_admin_keyboard());
                    } else {
                        bot_sendMessage($chat_id, "Siz admin emassiz!", get_reply_keyboard($username));
                    }
                    break;
                    
                case "➕ Kanal qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Kanal username (@kanal_nomi) yoki ID ni kiriting:");
                        set_user_state($uid, "admin_await_channel");
                    }
                    break;
                    
                case "❌ Kanal o'chirish":
                    if (in_array($username, ADMINS)) {
                        $channels = $config['channels'];
                        if (empty($channels)) {
                            bot_sendMessage($chat_id, "❗ Kanal ro'yxati bo'sh.", get_admin_keyboard());
                        } else {
                            $msg = "O'chirish uchun kanalni tanlang:\n" . implode("\n", $channels);
                            bot_sendMessage($chat_id, $msg);
                            set_user_state($uid, "admin_await_remove_channel");
                        }
                    }
                    break;
                    
                case "🆕 Tugma qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Yangi tugma nomini kiriting:");
                        set_user_state($uid, "admin_await_button_title");
                    }
                    break;
                    
                case "🗑 Tugma o'chirish":
                    if (in_array($username, ADMINS)) {
                        if (empty($buttons)) {
                            bot_sendMessage($chat_id, "❗ Tugma ro'yxati bo'sh.", get_admin_keyboard());
                        } else {
                            $msg = "O'chirish uchun tugmani tanlang:\n" . implode("\n", array_keys($buttons));
                            bot_sendMessage($chat_id, $msg);
                            set_user_state($uid, "admin_await_remove_button");
                        }
                    }
                    break;
                    
                case "🎬 Video qo'llanma qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Video sarlavhasini kiriting:");
                        set_user_state($uid, "admin_await_video_title");
                    }
                    break;
                    
                case "🗑 Video o'chirish":
                    if (in_array($username, ADMINS)) {
                        if (empty($videos)) {
                            bot_sendMessage($chat_id, "❗ Video ro'yxati bo'sh.", get_admin_keyboard());
                        } else {
                            $msg = "O'chirish uchun videoni tanlang:\n";
                            foreach ($videos as $vid_id => $meta) {
                                if (is_array($meta)) {
                                    $msg .= "📹 {$meta['title']}\n";
                                }
                            }
                            bot_sendMessage($chat_id, $msg . "\nVideo nomini kiriting:");
                            set_user_state($uid, "admin_await_remove_video");
                        }
                    }
                    break;
                    
                case "👤 Adminga murojaat sozlash":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "📎 Iltimos adminga murojaat uchun kontaktni kiriting.\nMasalan: @username yoki chat_id (raqam).");
                        set_user_state($uid, "admin_await_contact");
                    }
                    break;
                    
                case "📢 Reklama tarqatish":
                    if (in_array($username, ADMINS)) {
                        $kb = [
                            "keyboard" => [
                                [["text" => "📝 Faqat matn"]],
                                [["text" => "🖼 Rasm bilan"]],
                                [["text" => "🎬 Video bilan"]],
                                [["text" => "📎 Fayl bilan"]],
                                [["text" => "⬅️ Ortga"]]
                            ],
                            "resize_keyboard" => true
                        ];
                        bot_sendMessage($chat_id, "Reklama turini tanlang:", $kb);
                        set_user_state($uid, "admin_await_broadcast_type");
                    }
                    break;
                    
                case "🎁 Promokod qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Promokod nomini kiriting (masalan: YIL2025):");
                        set_user_state($uid, "admin_await_promocode_name");
                    }
                    break;
                    
                case "📋 Promokodlar":
                    if (in_array($username, ADMINS)) {
                        if (empty($promocodes)) {
                            bot_sendMessage($chat_id, "🎁 Promokodlar mavjud emas.", get_admin_keyboard());
                        } else {
                            $msg = "🎁 Promokodlar ro'yxati:\n\n";
                            foreach ($promocodes as $name => $pc) {
                                $used_count = isset($pc['used_by']) ? count($pc['used_by']) : 0;
                                $total_given = $used_count * $pc['amount'];
                                
                                $currency_icon = (isset($pc['type']) && $pc['type'] == 'coins') ? '🪙' : '💎';
                                $currency_name = (isset($pc['type']) && $pc['type'] == 'coins') ? 'tanga' : 'olmos';
                                
                                $msg .= "<b>{$name}</b>\n";
                                $msg .= "{$currency_icon} Mukofot: {$pc['amount']} {$currency_name} (har bir foydalanuvchi uchun)\n";
                                $msg .= "Foydalanganlar: {$used_count} ta\n";
                                $msg .= "Jami berilgan: {$total_given} {$currency_name}\n";
                                
                                if ($pc['limit_type'] == 'user_limit') {
                                    $remaining_uses = $pc['limit'] - $used_count;
                                    $msg .= "Limit: {$pc['limit']} ta odam\n";
                                    $msg .= "Qolgan: {$remaining_uses} ta\n";
                                } else {
                                    $expires_at = isset($pc['expires_at']) ? date('d.m.Y H:i', strtotime($pc['expires_at'])) : "Cheksiz";
                                    $msg .= "Muddati: {$expires_at}\n";
                                }
                                $msg .= "\n";
                            }
                            bot_sendMessage($chat_id, $msg, get_admin_keyboard(), "HTML");
                        }
                    }
                    break;
                    
                case "🗑 Promokod o'chirish":
                    if (in_array($username, ADMINS)) {
                        if (empty($promocodes)) {
                            bot_sendMessage($chat_id, "🎁 Promokodlar yo'q.", get_admin_keyboard());
                        } else {
                            $msg = "O'chirish uchun promokod nomini kiriting:\n" . implode("\n", array_keys($promocodes));
                            bot_sendMessage($chat_id, $msg);
                            set_user_state($uid, "admin_await_remove_promocode");
                        }
                    }
                    break;
                    
                case "➕ Admin qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Yangi admin username yoki ID sini kiriting:");
                        set_user_state($uid, "admin_await_new_admin");
                    }
                    break;

                case "👤 Olmos boshqarish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, 
                            "Olmos boshqarish:\nFormat: [add/remove] [username/ID] [miqdor]\nMasalan: add @username 50",
                            get_admin_keyboard());
                        set_user_state($uid, "admin_diamond_manage");
                    }
                    break;

                case "🪙 Tanga boshqarish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, 
                            "Tanga boshqarish:\nFormat: [add/remove] [username/ID] [miqdor]\nMasalan: add @username 50",
                            get_admin_keyboard());
                        set_user_state($uid, "admin_coin_manage");
                    }
                    break;
                    
                case "🎁 Bonus kanal qo'shish":
                    if (in_array($username, ADMINS)) {
                        bot_sendMessage($chat_id, "Bonus kanal linkini yuboring (masalan: https://t.me/insta_rekchi):");
                        set_user_state($uid, "admin_await_bonus_channel_link");
                    }
                    break;
                    
                case "🗑 Bonus kanal o'chirish":
                    if (in_array($username, ADMINS)) {
                        if (empty($channel_ads)) {
                            bot_sendMessage($chat_id, "❗ Bonus kanallar mavjud emas.", get_admin_keyboard());
                        } else {
                            $msg = "O'chirish uchun kanal ID sini kiriting:\n";
                            foreach ($channel_ads as $channel_id => $ad) {
                                $msg .= "📢 {$channel_id} - {$ad['reward']} olmos\n";
                            }
                            bot_sendMessage($chat_id, $msg);
                            set_user_state($uid, "admin_await_remove_bonus_channel");
                        }
                    }
                    break;
                    
                case "✨ Stiker qo'shish":
                    if (in_array($username, ADMINS)) {
                        $sticker_kb = [
                            [["text" => "🎬 Video qo'llanmani ko'rish tugmasi"], ["text" => "✅ Men videoni ko'rdim va tushundim tugmasi"]],
                            [["text" => "👁 1k tugmasi"], ["text" => "👁 5k tugmasi"], ["text" => "👁 10k tugmasi"]],
                            [["text" => "🔢 Ko'rishlar sonini kiritish tugmasi"], ["text" => "💎 Olmos yeg'ish tugmasi"]],
                            [["text" => "🛒 Do'kon tugmasi"], ["text" => "💰 Balans tugmasi"]],
                            [["text" => "🎁 Promokod tugmasi"], ["text" => "👑 Xizmatlar tugmasi"]],
                            [["text" => "📋 Tugmalar ro'yxati tugmasi"], ["text" => "⚙️ Admin panel tugmasi"]],
                            [["text" => "📊 Statistika tugmasi"], ["text" => "⬅️ Ortga tugmasi"]],
                            [["text" => "❌ Bekor qilish tugmasi"], ["text" => "🔄 Boshqa tugma"]],
                            [["text" => "✅ Xizmat muvaffaqiyatli tugmasi"], ["text" => "🎁 Promokod to'g'ri tugmasi"]],
                            [["text" => "🚀 Start tugmasi"], ["text" => "⬅️ Ortga"]]
                        ];
                        bot_sendMessage($chat_id, "Qaysi tugmaga stiker qo'shmoqchisiz?", ["keyboard" => $sticker_kb, "resize_keyboard" => true]);
                        set_user_state($uid, "admin_await_sticker_selection");
                    }
                    break;
                    
                case "🗑 Stiker o'chirish":
                    if (in_array($username, ADMINS)) {
                        if (empty($stickers)) {
                            bot_sendMessage($chat_id, "❗ Stikerlar mavjud emas.", get_admin_keyboard());
                        } else {
                            $sticker_names = [
                                'start' => 'Start tugmasi',
                                'subscribe_prompt' => 'Obuna so\'rovi',
                                'subscribe_success' => 'Obuna muvaffaqiyatli',
                                'service_success' => 'Xizmat muvaffaqiyatli',
                                'promocode_correct' => 'Promokod to\'g\'ri',
                                'video_watched' => 'Video ko\'rilgan'
                            ];
                            
                            $kb = [];
                            foreach ($stickers as $key => $sticker) {
                                $display_name = isset($sticker_names[$key]) ? $sticker_names[$key] : $key;
                                $kb[] = [["text" => $display_name]];
                            }
                            $kb[] = [["text" => "⬅️ Ortga"]];
                            
                            bot_sendMessage($chat_id, "O'chirish uchun stikerni tanlang:", ["keyboard" => $kb, "resize_keyboard" => true]);
                            set_user_state($uid, "admin_await_remove_sticker");
                        }
                    }
                    break;
                    
                default:
                    if (isset($buttons[$text])) {
                        send_button_sticker($chat_id, $text);
                        bot_sendMessage($chat_id, $buttons[$text]["msg"], get_reply_keyboard($username));
                    }
                    break;
            }
            break;
    }
}

if ($callback_query) {
    $chat_id = $callback_query['message']['chat']['id'];
    $user_id = $callback_query['from']['id'];
    $username = isset($callback_query['from']['username']) ? $callback_query['from']['username'] : "";
    $data = $callback_query['data'];
    $message_id = $callback_query['message']['message_id'];
    $callback_query_id = $callback_query['id'];
    $uid = strval($user_id);

    if ($data !== 'check_sub' && $data !== 'video_watched' && strpos($data, 'channel_subscribed') === false) {
        if (!is_user_subscribed_to_required_channels($user_id)) {
            ensure_subscribed_or_prompt($chat_id, $user_id, $username, $message_id, true, $callback_query_id);
            exit;
        }
    }

    if (strpos($data, "admin_warning|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 3) {
                $target_user_id = $parts[1];
                $video_url = $parts[2];
                
                bot_sendMessage($target_user_id,
                    "⚠️ OGOHLANTIRISH ⚠️\n\n" .
                    "Siz prasmotrga buyurtma berish paytida sizga berilgan xeshteg matnini vedeoyingizga joylamasdan prasmotrga buyurtma bergansiz.\n\n" .
                    "⚠️ Agar yana shu ishni takrorlasangiz balansingizdan -10 💎 ketadi.\n\n" .
                    "🔥Agar qanday buyurtma berishni bilmasangiz video qo'llanmani ko'ring ✅",
                    [
                        "inline_keyboard" => [
                            [["text" => "✅ Video qo'llanmani ko'rish", "callback_data" => "watch_tutorial"]]
                        ]
                    ]);
                
                if (isset($users[$target_user_id])) {
                    $users[$target_user_id]['hashtag_warnings'] = ($users[$target_user_id]['hashtag_warnings'] ?? 0) + 1;
                    save_user($target_user_id, $users[$target_user_id]);
                }
                
                $new_text = $callback_query['message']['text'] . "\n\n✅ Ogohlantirish yuborildi!";
                bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                answer_callback_query($callback_query_id, "✅ Ogohlantirish yuborildi!");
            }
        }
    }
    
    if (strpos($data, "admin_penalty|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 3) {
                $target_user_id = $parts[1];
                $video_url = $parts[2];
                
                $warning_count = $users[$target_user_id]['hashtag_warnings'] ?? 0;
                $penalty_amount = 10 + ($warning_count * 5);
                
                if (isset($users[$target_user_id])) {
                    if ($users[$target_user_id]['diamonds'] >= $penalty_amount) {
                        $users[$target_user_id]['diamonds'] -= $penalty_amount;
                        $users[$target_user_id]['hashtag_warnings'] = ($users[$target_user_id]['hashtag_warnings'] ?? 0) + 1;
                        save_user($target_uid, $users[$target_uid]);;
                        
                        bot_sendMessage($target_user_id,
                            "⚠️ Sizga (-{$penalty_amount}) 💎 ta olmos jarima berildi. Sababi : Buyurtma berish jarayonida siz biz bergan xeshteg matnini videoyingizga joylamasdan buyurtma bergansiz. Agar yana shunday qilishda davom etsangiz keyingi safar jarima kattalashadi ⚠️\n\n" .
                            "✅ Agar siz nega jarima solingani va qanday to'g'ri buyurtma bermoqchi bo'lsangiz video darslikni ko'rib o'rganib olishingiz mumkun 👇",
                            [
                                "inline_keyboard" => [
                                    [["text" => "✅ Video qo'llanma", "callback_data" => "watch_tutorial"]]
                                ]
                            ]);
                    } else {
                        bot_sendMessage($target_user_id,
                            "❌ JARIMA BERILDI!\n\n" .
                            "Siz videoda biz bergan hashtagni qo'shmagansiz!\n\n" .
                            "💎 Jarima miqdori: -{$penalty_amount} olmos\n" .
                            "💎 Sizda yetarli olmos yo'q, shuning uchun jarima berilmadi!\n\n" .
                            "Keyingi safar yana shunday qilsangiz, jarima miqdori oshadi!");
                    }
                }
                
                $new_text = $callback_query['message']['text'] . "\n\n✅ Jarima berildi! (-{$penalty_amount} olmos)";
                bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                answer_callback_query($callback_query_id, "✅ Jarima berildi!");
            }
        }
    }

    if (strpos($data, "admin_ok|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 3) {
                $target_user_id = $parts[1];
                $video_url = $parts[2];
                
                $new_text = $callback_query['message']['text'] . "\n\n✅ Muammo yo'q - tasdiqlandi!";
                bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                answer_callback_query($callback_query_id, "✅ Muammo yo'q!");
            }
        }
    }

    if (strpos($data, "user_sort|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 3) {
                $sort_by = $parts[1];
                $page = intval($parts[2]);
                $page_data = generate_user_list_page($page, $sort_by);
                bot_editMessageText($chat_id, $message_id, $page_data['text'], $page_data['reply_markup']);
                answer_callback_query($callback_query_id, "Tartib: " . $sort_by);
            }
        }
    }
    
    if (strpos($data, "user_list_page|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 3) {
                $sort_by = $parts[1];
                $page = intval($parts[2]);
                $page_data = generate_user_list_page($page, $sort_by);
                bot_editMessageText($chat_id, $message_id, $page_data['text'], $page_data['reply_markup']);
                answer_callback_query($callback_query_id, "Sahifa " . ($page + 1));
            }
        }
    }
    if (strpos($data, "service_shipments") === 0) {
        send_button_sticker($chat_id, "🚀 Jo'natishlar");
        bot_editMessageText($chat_id, $message_id, 
            "🚀 Jo'natishlar xizmati\n\n" .
            "Minimal: 100 ta\n" .
            "Narxi: 100 ta = 3 tanga\n\n" .
            "Kerakli jo'natishlar sonini kiriting:"
        );
        set_user_state($user_id, "waiting_for_shipments_count");
        answer_callback_query($callback_query_id, "Jo'natishlar sonini kiriting");
    }
    else if (strpos($data, "confirm_shipments") === 0) {
        $parts = explode('|', $data);
        $shipments_count = intval($parts[1]);
        $coins_needed = intval($parts[2]);
        
        $current_coins = get_user_balance($user_id, 'coins');
        if ($current_coins >= $coins_needed) {
            bot_editMessageText($chat_id, $message_id, 
                "🚀 Video havolasini yuboring:"
            );
            set_user_state($user_id, "waiting_for_shipments_link", [
                'shipments_count' => $shipments_count,
                'coins_needed' => $coins_needed
            ]);
        } else {
            bot_editMessageText($chat_id, $message_id, 
                "❌ Sizda yetarli tanga yo'q!\n" .
                "🪙 Sizda: {$current_coins} tanga\n" .
                "🪙 Kerak: {$coins_needed} tanga"
            );
        }
        answer_callback_query($callback_query_id);
    }
    else if (strpos($data, "cancel_shipments") === 0) {
        bot_editMessageText($chat_id, $message_id, "❌ Jo'natishlar xizmati bekor qilindi.");
        clear_user_state($user_id);
        answer_callback_query($callback_query_id, "Bekor qilindi");
    }
    if (strpos($data, "confirm_views|") === 0) {
        $parts = explode("|", $data);
        if (count($parts) === 3) {
            $views = intval($parts[1]);
            $diamonds = intval($parts[2]);
            
            $current_diamonds = get_user_balance($uid, 'diamonds');
            if ($current_diamonds < $diamonds) {
                answer_callback_query($callback_query_id, "❌ Sizda yetarli olmos yo'q!", true);
                exit;
            }
            
            $hs = get_hashtags($views);
            $users[$uid]["hashtags"] = $hs;
            $users[$uid]["await_video"] = true;
            $users[$uid]["views"] = $views;
            save_user($target_uid, $users[$target_uid]);;
            
            $ht_for_display = implode("\n", $hs);
            
            bot_editMessageText($chat_id, $message_id,
                "✅ Rozilik berildi!\n\n" .
                "🎯 {$views} ta ko'rish uchun mos heshteglar:\n<pre>{$ht_for_display}</pre>\n\n" .
                "📋 Hashteglarni nusxalab oling va videoyingizga qo'shing.\n" .
                "Va video havolasini menga yuborishingiz kerak ✅\n\n" .
                "⚠️ Akkauntingiz zakrit bo'lsa, prasmotrlar yeg'ilmaydi!",
                null, "HTML");
            
            bot_sendMessage($chat_id,
                "🔗 Marhamat, endi video havolasini yuboring...",
                get_reply_keyboard($username));
                
            set_user_state($uid, "waiting_for_video_link", [
                'views' => $views,
                'diamonds' => $diamonds
            ]);
            answer_callback_query($callback_query_id, "Video havolasini yuborishingiz mumkin!");
        }
    } elseif (strpos($data, "approve_diamonds|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 4) {
                $target_uid = $parts[1];
                $amount = intval($parts[2]);
                $price = intval($parts[3]);
                $users[$target_uid]["diamonds"] = isset($users[$target_uid]["diamonds"]) ? 
                    $users[$target_uid]["diamonds"] + $amount : $amount;
                save_user($target_uid, $users[$target_uid]);;
                
                if (isset($callback_query['message']['caption'])) {
                    $original_caption = $callback_query['message']['caption'];
                    $new_caption = $original_caption . "\n\n✅ Tasdiqlandi: @" . $username;
                    bot_editMessageCaption($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_caption, ["inline_keyboard" => []]);
                } elseif (isset($callback_query['message']['text'])) {
                    $original_text = $callback_query['message']['text'];
                    $new_text = $original_text . "\n\n✅ Tasdiqlandi: @" . $username;
                    bot_editMessageText($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_text, ["inline_keyboard" => []]);
                }
                
                send_sticker($target_uid, 'service_success');
                bot_sendMessage($target_uid, 
                    "✅ To'lovingiz tasdiqlandi! Hisobingizga {$amount} olmos qo'shildi.\n" .
                    "💎 Jami olmoslar: {$users[$target_uid]['diamonds']}");
                answer_callback_query($callback_query_id, "To'lov muvaffaqiyatli tasdiqlandi!");
            } else {
                answer_callback_query($callback_query_id, "❗ Noto'g'ri ma'lumot!", true);
            }
        } else {
            answer_callback_query($callback_query_id, "Sizda bunday huquq yo'q!", true);
        }
    } elseif (strpos($data, "reject_diamonds|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 4) {
                $target_uid = $parts[1];
                $amount = intval($parts[2]);
                $price = intval($parts[3]);
                
                if (isset($callback_query['message']['caption'])) {
                    $original_caption = $callback_query['message']['caption'];
                    $new_caption = $original_caption . "\n\n❌ Bekor qilindi: @" . $username;
                    bot_editMessageCaption($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_caption, ["inline_keyboard" => []]);
                } elseif (isset($callback_query['message']['text'])) {
                    $original_text = $callback_query['message']['text'];
                    $new_text = $original_text . "\n\n❌ Bekor qilindi: @" . $username;
                    bot_editMessageText($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_text, ["inline_keyboard" => []]);
                }
                
                bot_sendMessage($target_uid, 
                    "❌ Sizning to'lovingiz qabul qilinmadi. Iltimos, chekni tekshirib qayta yuboring yoki admin bilan bog'laning.");
                answer_callback_query($callback_query_id, "To'lov bekor qilindi!");
            } else {
                answer_callback_query($callback_query_id, "❗ Noto'g'ri ma'lumot!", true);
            }
        } else {
            answer_callback_query($callback_query_id, "Sizda bunday huquq yo'q!", true);
        }
        
    } elseif (strpos($data, "approve_coins|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 4) {
                $target_uid = $parts[1];
                $amount = intval($parts[2]);
                
                error_log("=== DEBUG APPROVE_COINS ===");
                error_log("Target User: {$target_uid}");
                error_log("Amount: {$amount}");
                
                // 1. Avval MySQL dan yangi ma'lumot olamiz
                $target_user = get_user($target_uid);
                error_log("Before - DB Coins: " . ($target_user['coins'] ?? 0));
                error_log("Before - Global Coins: " . ($users[$target_uid]['coins'] ?? 0));
                
                if (!$target_user) {
                    answer_callback_query($callback_query_id, "❌ Foydalanuvchi topilmadi!", true);
                    exit;
                }
                
                // 2. Global massivni yangilash
                $old_coins = $target_user['coins'] ?? 0;
                $new_coins = $old_coins + $amount;
                
                $users[$target_uid]['coins'] = $new_coins;
                
                // 3. MySQL ga SAQLASH
                $save_result = save_user($target_uid, $users[$target_uid]);
                error_log("Save result: " . ($save_result ? "SUCCESS" : "FAILED"));
                
                // 4. Yangi ma'lumotni tekshirish
                $updated_user = get_user($target_uid);
                error_log("After - DB Coins: " . ($updated_user['coins'] ?? 0));
                error_log("After - Global Coins: " . ($users[$target_uid]['coins'] ?? 0));
                error_log("=== DEBUG END ===");
                
                if ($save_result) {
                    // Xabarni yangilash
                    if (isset($callback_query['message']['caption'])) {
                        $new_caption = $callback_query['message']['caption'] . "\n\n✅ Tasdiqlandi: @" . $username . "\n🪙 Yangi balans: {$new_coins}";
                        bot_editMessageCaption($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_caption, ["inline_keyboard" => []]);
                    } elseif (isset($callback_query['message']['text'])) {
                        $new_text = $callback_query['message']['text'] . "\n\n✅ Tasdiqlandi: @" . $username . "\n🪙 Yangi balans: {$new_coins}";
                        bot_editMessageText($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_text, ["inline_keyboard" => []]);
                    }
                    
                    // Foydalanuvchiga xabar
                    send_sticker($target_uid, 'service_success');
                    bot_sendMessage($target_uid, 
                        "✅ To'lovingiz tasdiqlandi! Hisobingizga {$amount} tanga qo'shildi.\n" .
                        "🪙 Avval: {$old_coins} tanga\n" .
                        "🪙 Yangi: {$new_coins} tanga");
                        
                    answer_callback_query($callback_query_id, "✅ {$amount} tanga qo'shildi! Yangi balans: {$new_coins}");
                } else {
                    answer_callback_query($callback_query_id, "❌ Saqlashda xatolik!", true);
                }
            }
        }
    } elseif (strpos($data, "reject_coins|") === 0) {
        if (in_array($username, ADMINS)) {
            $parts = explode("|", $data);
            if (count($parts) === 4) {
                $target_uid = $parts[1];
                $amount = intval($parts[2]);
                $price = intval($parts[3]);
                
                if (isset($callback_query['message']['caption'])) {
                    $original_caption = $callback_query['message']['caption'];
                    $new_caption = $original_caption . "\n\n❌ Bekor qilindi: @" . $username;
                    bot_editMessageCaption($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_caption, ["inline_keyboard" => []]);
                } elseif (isset($callback_query['message']['text'])) {
                    $original_text = $callback_query['message']['text'];
                    $new_text = $original_text . "\n\n❌ Bekor qilindi: @" . $username;
                    bot_editMessageText($callback_query['message']['chat']['id'], $callback_query['message']['message_id'], $new_text, ["inline_keyboard" => []]);
                }
                
                bot_sendMessage($target_uid, 
                    "❌ Sizning to'lovingiz qabul qilinmadi. Iltimos, chekni tekshirib qayta yuboring yoki admin bilan bog'laning.");
                answer_callback_query($callback_query_id, "To'lov bekor qilindi!");
            } else {
                answer_callback_query($callback_query_id, "❗ Noto'g'ri ma'lumot!", true);
            }
        } else {
            answer_callback_query($callback_query_id, "Sizda bunday huquq yo'q!", true);
        }
        
    } elseif (strpos($data, "channel_subscribed|") === 0) {
        $parts = explode("|", $data);
        if (count($parts) === 2) {
            $channel_id = $parts[1];
            $result = process_channel_subscription($user_id, $channel_id);
            
            if ($result === "success") {
                send_sticker($user_id, 'service_success');
                
                $reward = $channel_ads[$channel_id]['reward'];
                $current_diamonds = $users[$uid]['diamonds'];
                
                // process_channel_subscription funksiyasidagi muvaffaqiyatli xabarni yangilang:
                bot_sendMessage($user_id,
                    "🎉 TABRIKLAYMIZ! BONUS OLGANINGIZ UCHUN RAXMAT! 🎉\n\n" .
                    "📢 Kanal: {$channel_id}\n" .
                    "💎 Mukofot: +{$reward} olmos\n" .
                    "💎 Jami olmoslar: {$current_diamonds}\n\n" .
                    "✅ Kanalga obuna bo'lganingiz uchun rahmat!",
                    [
                        "inline_keyboard" => [
                            [["text" => "💎 Asosiy menyu", "callback_data" => "main_menu"]]
                        ]
                    ], "HTML");
                answer_callback_query($callback_query_id, "✅ {$reward} olmos berildi!");
                
            } elseif ($result === "already_claimed") {
                answer_callback_query($callback_query_id, "❌ Siz bu kanaldan allaqachon mukofot olgansiz!", true);
                
            } elseif ($result === "not_subscribed") {
                if ($users[$uid]['penalty_warnings'] == 0) {
                    $users[$uid]['penalty_warnings'] = 1;
                    save_user($target_uid, $users[$target_uid]);;
                    
                    answer_callback_query($callback_query_id, 
                        "⚠️ DIQQAT! Siz kanalga obuna bo'lmagansiz!\n" .
                        "❗ Iltimos, avval kanalga obuna bo'ling, keyin 'Obuna bo'ldim' tugmasini bosing!\n" .
                        "Keyingi marta noto'g'ri ma'lumot berilsa, jarima beriladi!", 
                        true);
                        
                    bot_sendMessage($user_id,
                        "⚠️ OGOHLANTIRISH!\n\n" .
                        "Siz kanalga obuna bo'lmaganingiz holda 'Obuna bo'ldim' tugmasini bosdingiz!\n" .
                        "Keyingi safar shunday qilsangiz, " . ($channel_ads[$channel_id]['reward'] + 1) . " olmos jarimaga tortilasiz!\n\n" .
                        "Iltimos, kanalga obuna bo'ling va keyin tekshirish tugmasini bosing."
                    );
                    
                } else {
                    $penalty_amount = $channel_ads[$channel_id]['reward'] + 1;
                    if ($users[$uid]['diamonds'] >= $penalty_amount) {
                        $users[$uid]['diamonds'] -= $penalty_amount;
                        $users[$uid]['penalty_warnings'] += 1;
                        save_user($target_uid, $users[$target_uid]);;
                        
                        answer_callback_query($callback_query_id, 
                            "❌ NOTO'G'RI HAРАКАТ!\n" .
                            "Siz kanalga obuna bo'lmagansiz!\n" .
                            "💎 Jarima: -{$penalty_amount} olmos\n" .
                            "💎 Qolgan olmoslar: {$users[$uid]['diamonds']}\n\n" .
                            "⚠️ Iltimos, kanalga obuna bo'ling!", 
                            true);
                            
                        bot_sendMessage($user_id,
                            "❌ JARIMA BERILDI!\n\n" .
                            "Siz kanalga obuna bo'lmaganingiz holda 'Obuna bo'ldim' tugmasini bosdingiz!\n" .
                            "💎 Jarima miqdori: -{$penalty_amount} olmos\n" .
                            "💎 Qolgan olmoslar: {$users[$uid]['diamonds']}\n\n" .
                            "Keyingi safar yana shunday qilsangiz, jarima miqdori oshadi!"
                        );
                    } else {
                        answer_callback_query($callback_query_id, 
                            "❌ NOTO'G'RI HAРАКAT!\n" .
                            "Siz kanalga obuna bo'lmagansiz!\n" .
                            "💎 Jarima berish uchun olmoslaringiz yetarli emas!\n\n" .
                            "⚠️ Iltimos, kanalga obuna bo'ling!", 
                            true);
                    }
                }
            }
        }
        
    } else if (strpos($data, 'service_shipments') === 0) {
        send_button_sticker($callback_chat_id, "🚀 Jo'natishlar");
        bot_editMessageText($callback_chat_id, $message_id, 
            "🚀 Jo'natishlar xizmati\n\n" .
            "Minimal: 100 ta\n" .
            "Narxi: 100 ta = 3 tanga\n\n" .
            "Kerakli jo'natishlar sonini kiriting:"
        );
        set_user_state($callback_user_id, "waiting_for_shipments_count");
        answer_callback_query($callback_query['id'], "Jo'natishlar sonini kiriting");
    }

    else if (strpos($data, 'confirm_shipments') === 0) {
        $parts = explode('|', $data);
        $shipments_count = intval($parts[1]);
        $coins_needed = intval($parts[2]);
        
        $current_coins = $users[$callback_user_id]['coins'] ?? 0;
        if ($current_coins >= $coins_needed) {
            bot_editMessageText($callback_chat_id, $message_id, 
                "🚀 Video havolasini yuboring:"
            );
            set_user_state($callback_user_id, "waiting_for_shipments_link", [
                'shipments_count' => $shipments_count,
                'coins_needed' => $coins_needed
            ]);
        } else {
            bot_editMessageText($callback_chat_id, $message_id, 
                "❌ Sizda yetarli tanga yo'q!\n" .
                "🪙 Sizda: {$current_coins} tanga\n" .
                "🪙 Kerak: {$coins_needed} tanga"
            );
        }
        answer_callback_query($callback_query['id']);
    }
    else if (strpos($data, 'cancel_shipments') === 0) {
        bot_editMessageText($callback_chat_id, $message_id, "❌ Jo'natishlar xizmati bekor qilindi.");
        clear_user_state($callback_user_id);
        answer_callback_query($callback_query['id'], "Bekor qilindi");
    } else if (strpos($data, 'channel_subscribed') === 0) {
        $parts = explode('|', $data);
        $channel_id = $parts[1];
        
        // Natijani qayta ishlash
        $result = process_channel_subscription($callback_user_id, $channel_id);
        
        if ($result == "success") {
            $ad = $channel_ads[$channel_id];
            $reward = $ad['reward'];
            $current_balance = get_user_balance($callback_user_id, 'diamonds');
            
            send_sticker($callback_chat_id, 'subscribe_success');
            
            $message = "🎉 Tabriklaymiz!\n\n" .
                    "✅ Siz kanalga obuna bo'ldingiz va bonus olmosni qo'lga kiritdingiz!\n\n" .
                    "📢 Kanal: {$channel_id}\n" .
                    "💎 Bonus: +{$reward} olmos\n" .
                    "💎 Jami olmoslar: {$current_balance}";
            
            bot_editMessageText($callback_chat_id, $message_id, $message);
            
        } else if ($result == "already_claimed") {
            $message = "ℹ️ Siz ushbu kanaldan bonus olib bo'lgansiz!\n\n" .
                    "📢 Kanal: {$channel_id}\n" .
                    "✅ Holat: Bonus allaqachon berilgan\n\n" .
                    "Boshqa bonus kanallarni tekshiring!";
            
            bot_editMessageText($callback_chat_id, $message_id, $message);
            
        } else if ($result == "not_subscribed") {
            $message = "❌ Hali obuna bo'lmagansiz!\n\n" .
                    "📢 Kanal: {$channel_id}\n" .
                    "ℹ️ Iltimos, avval kanalga obuna bo'ling, so'ng \"Obuna bo'ldim\" tugmasini bosing.";
            
            bot_editMessageText($callback_chat_id, $message_id, $message);
            
        } else if ($result == "channel_not_found") {
            $message = "❌ Kanal topilmadi!\n\n" .
                    "Ushbu bonus kanal mavjud emas yoki o'chirilgan.";
            
            bot_editMessageText($callback_chat_id, $message_id, $message);
            
        } else {
            $message = "❌ Xatolik yuz berdi!\n\n" .
                    "Iltimos, birozdan keyin qayta urinib ko'ring yoki admin bilan bog'laning.";
            
            bot_editMessageText($callback_chat_id, $message_id, $message);
        }
        
        answer_callback_query($callback_query['id'], "Tekshirildi");
    } else {
        switch ($data) {
            case "video_watched":
                if (!$users[$uid]["video_watched"]) {
                    send_sticker($chat_id, 'video_watched');
                    
                    $bonus_diamonds = 12;
                    $users[$uid]["diamonds"] += $bonus_diamonds;
                    $users[$uid]["video_watched"] = true;
                    save_user($target_uid, $users[$target_uid]);;
                    
                    $current_diamonds = $users[$uid]["diamonds"];
                    
                    bot_sendMessage($chat_id, 
                        "🎉 Tabriklaymiz! Video qo'llanmani muvaffaqiyatli ko'rib tushundingiz!\n" .
                        "💎 Mukofot: +{$bonus_diamonds} olmos\n" .
                        "💎 Jami olmoslar: <b>{$current_diamonds}</b>\n\n" .
                        "Endi asosiy menyu ochildi, xizmatlardan foydalanishingiz mumkin!",
                        get_reply_keyboard($username), "HTML");
                    answer_callback_query($callback_query_id, "✅ +{$bonus_diamonds} olmos berildi!");
                } else {
                    answer_callback_query($callback_query_id, "❌ Siz allaqachon video qo'llanmani ko'rib tushunganingiz!", true);
                }
                break;
                
            case "watch_tutorial":
                if (!empty($videos)) {
                    $first_video_key = array_key_first($videos);
                    send_video_tutorial($chat_id, $first_video_key, false);
                    answer_callback_query($callback_query_id, "✅ Video yuborildi!");
                } else {
                    answer_callback_query($callback_query_id, "🎬 Video qo'llanmalar mavjud emas!", true);
                }
                break;
                
            case "free_diamonds":
                $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                $ref_count = get_valid_ref_count($uid);
                
                $ref_link = "https://t.me/" . BOT_USERNAME . "?start=" . $uid;
                
                $message = "💎 OLMOS YEG'ISH USULLARI 💎\n\n";
                
                $message .= "👫 DO'STLARNI TAKLIF QILING\n";
                $message .= "Har bir do'stingiz uchun +15 💎 olmos oling!\n\n";
                $message .= "🔗 Sizning referal havolangiz:\n<code>{$ref_link}</code>\n\n";
                $message .= "📊 Tasdiqlangan referallar: <b>{$ref_count} ta</b>\n";
                $message .= "💎 Jami olmoslar: <b>{$current_diamonds}</b>";
                
                $kb = make_earn_diamonds_kb();
                
                bot_sendMessage($chat_id, $message, $kb, "HTML");
                answer_callback_query($callback_query_id);
                break;
           // callback_query qismida quyidagi kod blokini toping va butunlay shu bilan almashtiring:
            case "open_shop":
                send_button_sticker($chat_id, "🛒 Do'kon");
                $current_diamonds = $users[$uid]["diamonds"] ?? 0;
                $current_coins = $users[$uid]["coins"] ?? 0;
                $shop_text = "💎 Do'kon\n\n";
                $shop_text .= "💰 Jami olmoslar: <b>{$current_diamonds}</b>\n";
                $shop_text .= "🪙 Jami tangalar: <b>{$current_coins}</b>\n\n";
                $shop_text .= "Quyidagi valyutalardan birini tanlang:";
                
                $kb = [
                    "inline_keyboard" => [
                        [["text" => "💎 Olmos sotib olish", "callback_data" => "buy_diamonds"]],
                        [["text" => "🪙 Tanga sotib olish", "callback_data" => "buy_coins"]]
                    ]
                ];
                
                bot_sendMessage($chat_id, $shop_text, $kb, "HTML");
                answer_callback_query($callback_query_id);
                break;

            case "buy_diamonds":
                $diamonds_text = "💎 OLMOS PAKETLARI:\n\n";
                $diamonds_text .= "• 50 olmos - 5,000 So'm\n";
                $diamonds_text .= "• 100 olmos - 9,000 So'm\n"; 
                $diamonds_text .= "• 300 olmos - 25,000 So'm\n\n";
                $diamonds_text .= "Yoki boshqa miqdorda olmos xarid qilishingiz mumkin:";
                
                $kb = make_shop_contact_kb('diamonds');
                bot_sendMessage($chat_id, $diamonds_text, $kb);
                answer_callback_query($callback_query_id);
                break;

            case "buy_coins":
                $coins_text = "🪙 TANGA PAKETLARI:\n\n";
                $coins_text .= "• 50 🪙 tanga = 5,000 So'm\n";
                $coins_text .= "• 100 🪙 tanga = 9,000 So'm\n";
                $coins_text .= "• 500 🪙 tanga = 42,000 So'm\n";
                $coins_text .= "• 1000 🪙 tanga = 72,000 So'm\n\n";
                $coins_text .= "To'lovni amalga oshirish uchun admin bilan bog'laning:";
                $kb = make_shop_contact_kb('coins');
                bot_sendMessage($chat_id, $coins_text, $kb);
                answer_callback_query($callback_query_id);
                break;

            case "buy_coins_direct":
                $coins_text = "🪙 TANGA PAKETLARI:\n\n";
                $coins_text .= "• 50 🪙 tanga = 5,000 So'm\n";
                $coins_text .= "• 100 🪙 tanga = 9,000 So'm\n";
                $coins_text .= "• 500 🪙 tanga = 42,000 So'm\n";
                $coins_text .= "• 1000 🪙 tanga = 72,000 So'm\n\n";
                $coins_text .= "Yoki boshqa miqdorda tanga xarid qilishingiz mumkin:";
                
                $kb = make_shop_contact_kb('coins');
                bot_sendMessage($chat_id, $coins_text, $kb);
                answer_callback_query($callback_query_id);
                break;

            case "cancel_views":
                bot_sendMessage($chat_id, "❌ Xizmat bekor qilindi.", get_reply_keyboard($username));
                clear_user_state($uid);
                answer_callback_query($callback_query_id, "Bekor qilindi!");
                break;

            case "cancel_topup":
                bot_sendMessage($chat_id, "❌ Xarid bekor qilindi.", get_reply_keyboard($username));
                clear_user_state($uid);
                answer_callback_query($callback_query_id, "Bekor qilindi!");
                break;
                
            case "check_sub":
                error_log("Tekshirish tugmasi bosildi: User {$user_id}");
                
                // ensure_subscribed_or_prompt funksiyasini chaqirish
                ensure_subscribed_or_prompt($chat_id, $user_id, $username, $message_id, true, $callback_query_id);
                break;
            case "service_likes":
                bot_sendMessage($chat_id, "❤️ Sizga nechta like kerak? (minimal 50)");
                set_user_state($uid, "waiting_for_likes_count");
                answer_callback_query($callback_query_id, "Like sonini kiriting");
                break;
                
            case "service_followers":
                bot_sendMessage($chat_id, "👥 Sizga nechta obunachi kerak? (minimal 10)");
                set_user_state($uid, "waiting_for_followers_count");
                answer_callback_query($callback_query_id, "Obunachilar sonini kiriting");
                break;

            case strpos($data, "confirm_likes|") === 0:
                $parts = explode("|", $data);
                if (count($parts) === 3) {
                    $likes = intval($parts[1]);
                    $coins = intval($parts[2]);
                    $current_coins = $users[$uid]["coins"] ?? 0;
                    if ($current_coins < $coins) {
                        answer_callback_query($callback_query_id, "❌ Sizda yetarli tanga yo'q!", true);
                        exit;
                    }
                    set_user_state($uid, "waiting_for_likes_link", ['likes_count' => $likes, 'coins_needed' => $coins]);
                    bot_editMessageText($chat_id, $message_id,
                        "✅ Rozilik olindi!\n\nEndi video havolasini yuboring (http...):\n\nAgar havola bilan muammo bo'lsa admin bilan bog'laning.",
                        null);
                    answer_callback_query($callback_query_id, "Video havolasini yuborishingiz mumkin!");
                }
                break;
            case strpos($data, "confirm_views|") === 0:
                $parts = explode("|", $data);
                if (count($parts) === 3) {
                    $views = intval($parts[1]);
                    $diamonds = intval($parts[2]);
                    
                    $current_diamonds = get_user_balance($uid, 'diamonds');
                    if ($current_diamonds < $diamonds) {
                        answer_callback_query($callback_query_id, "❌ Sizda yetarli olmos yo'q!", true);
                        exit;
                    }
                    
                    $hs = get_hashtags($views);
                    $users[$uid]["hashtags"] = $hs;
                    $users[$uid]["await_video"] = true;
                    $users[$uid]["views"] = $views;
                    save_user($target_uid, $users[$target_uid]);;
                    
                    $ht_for_display = implode("\n", $hs);
                    
                    bot_editMessageText($chat_id, $message_id,
                        "✅ Rozilik berildi!\n\n" .
                        "🎯 " . number_format($views) . " ta ko'rish uchun mos heshteglar:\n<pre>{$ht_for_display}</pre>\n\n" .
                        "📋 Hashteglarni nusxalab oling va videoyingizga qo'shing.\n" .
                        "Va video havolasini menga yuborishingiz kerak ✅\n\n" .
                        "⚠️ Akkauntingiz zakrit bo'lsa, prasmotrlar yeg'ilmaydi!",
                        null, "HTML");
                    
                    bot_sendMessage($chat_id,
                        "🔗 Marhamat, endi video havolasini yuboring...",
                        get_reply_keyboard($username));
                        
                    set_user_state($uid, "waiting_for_video_link", [
                        'views' => $views,
                        'diamonds' => $diamonds
                    ]);
                    answer_callback_query($callback_query_id, "Video havolasini yuborishingiz mumkin!");
                }
                break;
            case strpos($data, "confirm_followers|") === 0:
                $parts = explode("|", $data);
                if (count($parts) === 3) {
                    $followers = intval($parts[1]);
                    $coins = intval($parts[2]);
                    $current_coins = $users[$uid]["coins"] ?? 0;
                    if ($current_coins < $coins) {
                        answer_callback_query($callback_query_id, "❌ Sizda yetarli tanga yo'q!", true);
                        exit;
                    }
                    set_user_state($uid, "waiting_for_followers_username", ['followers_count' => $followers, 'coins_needed' => $coins]);
                    bot_editMessageText($chat_id, $message_id,
                        "✅ Rozilik olindi!\n\nIltimos, Instagram profil nomini yuboring (masalan: username).",
                        null);
                    answer_callback_query($callback_query_id, "Profil nomini yuboring!");
                }
                break;

            case "cancel_likes":
                clear_user_state($uid);
                bot_editMessageText($chat_id, $message_id, "❌ Like xizmati bekor qilindi.", null);
                answer_callback_query($callback_query_id, "Bekor qilindi!");
                break;

            case "cancel_followers":
                clear_user_state($uid);
                bot_editMessageText($chat_id, $message_id, "❌ Obunachilar xizmati bekor qilindi.", null);
                answer_callback_query($callback_query_id, "Bekor qilindi!");
                break;
                
            case "my_referrals":
                $referrals_info = get_referrals_info($uid);
                bot_sendMessage($chat_id, $referrals_info, get_reply_keyboard($username));
                answer_callback_query($callback_query_id, "✅ Referallar statistikasi");
                break;
                
            case "share_invite":
                $ref_link = "https://t.me/" . BOT_USERNAME . "?start=" . $uid;
                $share_text = "👆Bu suniy intelekt sizni instagramdagi videolaringizni mutlaqo bepul rekka chiqarib beradi.";
                
                $kb = [
                    "inline_keyboard" => [
                        [["text" => "📤 Do'stlarga ulashish", "url" => "https://t.me/share/url?url=" . urlencode($ref_link) . "&text=" . urlencode($share_text)]]
                    ]
                ];
                
                bot_sendMessage($chat_id, 
                    "📤 Do'stlaringizni taklif qilish uchun quyidagi tugmani bosing:\n\n" .
                    "🔗 Havola: <code>{$ref_link}</code>", 
                    $kb, "HTML");
                answer_callback_query($callback_query_id);
                break;
                
            case "user_list":
                if (in_array($username, ADMINS)) {
                    $page_data = generate_user_list_page(0);
                    bot_sendMessage($chat_id, $page_data['text'], $page_data['reply_markup']);
                    answer_callback_query($callback_query_id, "👥 Foydalanuvchilar ro'yxati");
                }
                break;
                
            case "order_list":
                if (in_array($username, ADMINS)) {
                    bot_sendMessage($chat_id, 
                        "📦 Buyurtmalar ro'yxatini ko'rish uchun foydalanuvchi nomini yoki ID sini kiriting:",
                        get_admin_keyboard());
                    set_user_state($uid, "admin_await_order_user");
                    answer_callback_query($callback_query_id, "Foydalanuvchi nomini kiriting");
                }
                break;
                
            case "promo_stats":
                if (in_array($username, ADMINS)) {
                    $promo_stats = get_promocode_statistics();
                    bot_sendMessage($chat_id, $promo_stats, get_admin_keyboard());
                    answer_callback_query($callback_query_id, "📊 Promokod statistika");
                }
                break;
                
            case "send_user_message":
                if (in_array($username, ADMINS)) {
                    bot_sendMessage($chat_id, 
                        "✉️ Xabar yubormoqchi bo'lgan foydalanuvchi nomini yoki ID sini kiriting:",
                        get_admin_keyboard());
                    set_user_state($uid, "admin_await_message_user");
                    answer_callback_query($callback_query_id, "Foydalanuvchi nomini kiriting");
                }
                break;
                
            case "main_menu":
                bot_sendMessage($chat_id, "🏠 Asosiy menyu:", get_reply_keyboard($username));
                answer_callback_query($callback_query_id, "Asosiy menyu");
                break;

            case "copy_card":
                $card_number = TOPUP_CARD_NUMBER;
                bot_sendMessage($chat_id, 
                    "💳 Karta raqami nusxalandi:\n<code>{$card_number}</code>\n\n" .
                    "Endi ushbu raqamga to'lov qiling va chekni yuboring.",
                    null, "HTML");
                answer_callback_query($callback_query_id, "✅ Karta raqami nusxalandi!");
                break;
                
            // Callback query qismida promokod bilan bog'liq qismlarni toping va quyidagicha o'zgartiring:

            // Callback query switch qismini toping va quyidagicha o'zgartiring:

            case "send_promo_to_channel":
                $parts = explode("|", $data);
                if (count($parts) === 2) {
                    $promo_name = $parts[1];
                    
                    // Promokodni kanalga yuborish
                    if (send_promocode_to_channel($promo_name)) {
                        // Xabarni yangilash
                        $new_text = "✅ Promokod kanalga muvaffaqiyatli yuborildi!\n\nKod: <b>{$promo_name}</b>";
                        if (isset($callback_query['message']['text'])) {
                            bot_editMessageText($chat_id, $message_id, $new_text, null, "HTML");
                        } elseif (isset($callback_query['message']['caption'])) {
                            bot_editMessageCaption($chat_id, $message_id, $new_text, null, "HTML");
                        }
                        answer_callback_query($callback_query_id, "✅ Promokod kanalga yuborildi!");
                    } else {
                        answer_callback_query($callback_query_id, "❌ Promokodni kanalga yuborishda xatolik!", true);
                    }
                } else {
                    answer_callback_query($callback_query_id, "❌ Noto'g'ri ma'lumot format!", true);
                }
                break;
                
            case "dont_send_promo":
                $parts = explode("|", $data);
                if (count($parts) === 2) {
                    $promo_name = $parts[1];
                    // Xabarni yangilash
                    $new_text = "✅ Promokod qo'shildi! (Kanalga yuborilmadi)\n\nKod: <b>{$promo_name}</b>";
                    if (isset($callback_query['message']['text'])) {
                        bot_editMessageText($chat_id, $message_id, $new_text, null, "HTML");
                    } elseif (isset($callback_query['message']['caption'])) {
                        bot_editMessageCaption($chat_id, $message_id, $new_text, null, "HTML");
                    }
                    answer_callback_query($callback_query_id, "✅ Promokod saqlandi!");
                } else {
                    answer_callback_query($callback_query_id, "❌ Noto'g'ri ma'lumot format!", true);
                }
                break;
            case strpos($data, "admin_warning_order|") === 0:
                if (in_array($username, ADMINS)) {
                    $parts = explode("|", $data);
                    if (count($parts) === 2) {
                        $order_id = $parts[1];
                        $order = $orders[$order_id] ?? null;
                        
                        if ($order) {
                            $target_user_id = $order['user_id'];
                            
                            bot_sendMessage($target_user_id,
                                "⚠️ OGOHLANTIRISH ⚠️\n\n" .
                                "Siz prasmotrga buyurtma berish paytida sizga berilgan xeshteg matnini vedeoyingizga joylamasdan prasmotrga buyurtma bergansiz.\n\n" .
                                "⚠️ Agar yana shu ishni takrorlasangiz balansingizdan -10 💎 ketadi.\n\n" .
                                "🔥Agar qanday buyurtma berishni bilmasangiz video qo'llanmani ko'ring ✅",
                                [
                                    "inline_keyboard" => [
                                        [["text" => "✅ Video qo'llanmani ko'rish", "callback_data" => "watch_tutorial"]]
                                    ]
                                ]);
                            
                            if (isset($users[$target_user_id])) {
                                $users[$target_user_id]['hashtag_warnings'] = ($users[$target_user_id]['hashtag_warnings'] ?? 0) + 1;
                                save_user($target_uid, $users[$target_uid]);;
                            }
                            
                            $new_text = $callback_query['message']['text'] . "\n\n✅ Ogohlantirish yuborildi!";
                            bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                            answer_callback_query($callback_query_id, "✅ Ogohlantirish yuborildi!");
                        }
                    }
                }
                break;

            case strpos($data, "admin_penalty_order|") === 0:
                if (in_array($username, ADMINS)) {
                    $parts = explode("|", $data);
                    if (count($parts) === 2) {
                        $order_id = $parts[1];
                        $order = $orders[$order_id] ?? null;
                        
                        if ($order) {
                            $target_user_id = $order['user_id'];
                            $warning_count = $users[$target_user_id]['hashtag_warnings'] ?? 0;
                            $penalty_amount = 10 + ($warning_count * 5);
                            
                            if (isset($users[$target_user_id])) {
                                if ($users[$target_user_id]['diamonds'] >= $penalty_amount) {
                                    $users[$target_user_id]['diamonds'] -= $penalty_amount;
                                    $users[$target_user_id]['hashtag_warnings'] = ($users[$target_user_id]['hashtag_warnings'] ?? 0) + 1;
                                    save_user($target_uid, $users[$target_uid]);;
                                    
                                    bot_sendMessage($target_user_id,
                                        "⚠️ Sizga (-{$penalty_amount}) 💎 ta olmos jarima berildi. Sababi : Buyurtma berish jarayonida siz biz bergan xeshteg matnini videoyingizga joylamasdan buyurtma bergansiz. Agar yana shunday qilishda davom etsangiz keyingi safar jarima kattalashadi ⚠️\n\n" .
                                        "✅ Agar siz nega jarima solingani va qanday to'g'ri buyurtma bermoqchi bo'lsangiz video darslikni ko'rib o'rganib olishingiz mumkun 👇",
                                        [
                                            "inline_keyboard" => [
                                                [["text" => "✅ Video qo'llanma", "callback_data" => "watch_tutorial"]]
                                            ]
                                        ]);
                                } else {
                                    bot_sendMessage($target_user_id,
                                        "❌ JARIMA BERILDI!\n\n" .
                                        "Siz videoda biz bergan hashtagni qo'shmagansiz!\n\n" .
                                        "💎 Jarima miqdori: -{$penalty_amount} olmos\n" .
                                        "💎 Sizda yetarli olmos yo'q, shuning uchun jarima berilmadi!\n\n" .
                                        "Keyingi safar yana shunday qilsangiz, jarima miqdori oshadi!");
                                }
                            }
                            
                            $new_text = $callback_query['message']['text'] . "\n\n✅ Jarima berildi! (-{$penalty_amount} olmos)";
                            bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                            answer_callback_query($callback_query_id, "✅ Jarima berildi!");
                        }
                    }
                }
                break;

            case strpos($data, "admin_ok_order|") === 0:
                if (in_array($username, ADMINS)) {
                    $parts = explode("|", $data);
                    if (count($parts) === 2) {
                        $order_id = $parts[1];
                        $order = $orders[$order_id] ?? null;
                        
                        if ($order) {
                            $new_text = $callback_query['message']['text'] . "\n\n✅ Muammo yo'q - tasdiqlandi!";
                            bot_editMessageText($chat_id, $message_id, $new_text, ["inline_keyboard" => []]);
                            answer_callback_query($callback_query_id, "✅ Muammo yo'q!");
                        }
                    }
                }
                break; 
            default:
                if (strpos($data, "v") === 0) {
                    $index = intval(substr($data, 1));
                    $user_state_data = get_user_state($uid);
                    $video_list = $user_state_data && isset($user_state_data['data']['videos']) ? $user_state_data['data']['videos'] : [];
                    if (isset($video_list[$index])) {
                        $video_id = $video_list[$index];
                        send_video_tutorial($chat_id, $video_id, false);
                        answer_callback_query($callback_query_id, "✅ Video yuborildi!");
                    } else {
                        answer_callback_query($callback_query_id, "❌ Video topilmadi!", true);
                    }
                } else {
                    answer_callback_query($callback_query_id, "Noma'lum tugma: {$data}", true);
                }
                break;
        }
    }
}
function validate_balances() {
    global $users;
    
    $errors = [];
    $fixed = 0;
    
    foreach ($users as $user_id => $user_data) {
        // Balans qiymatlarini tekshirish
        if (isset($user_data['diamonds']) && (!is_numeric($user_data['diamonds']) || $user_data['diamonds'] < 0)) {
            $users[$user_id]['diamonds'] = max(0, intval($user_data['diamonds']));
            $errors[] = "User {$user_id}: Diamonds fixed to {$users[$user_id]['diamonds']}";
            $fixed++;
        }
        
        if (isset($user_data['coins']) && (!is_numeric($user_data['coins']) || $user_data['coins'] < 0)) {
            $users[$user_id]['coins'] = max(0, intval($user_data['coins']));
            $errors[] = "User {$user_id}: Coins fixed to {$users[$user_id]['coins']}";
            $fixed++;
        }
        
        // Ma'lumotlarni saqlash
        save_user($user_id, $users[$user_id]);
    }
    
    if (!empty($errors)) {
        error_log("Balans tekshiruvi: {$fixed} ta xatolik tuzatildi");
        foreach ($errors as $error) {
            error_log($error);
        }
    }
    
    return $fixed;
}
function save_all_users() {
    global $users;
    
    $db = get_db_connection();
    $mysql_connected = ($db !== false);
    
    if ($mysql_connected) {
        // MySQL ga barcha foydalanuvchilarni saqlash
        $success_count = 0;
        foreach ($users as $user_id => $user_data) {
            if (save_user($user_id, $user_data)) {
                $success_count++;
            }
        }
        error_log("MySQL ga {$success_count}/" . db_get_users_count() . " foydalanuvchi saqlandi");
    } else {
        // Agar MySQL ulanmasa, JSON ga saqlash
        error_log("MySQL ulanmadi, JSON ga saqlanmoqda");
        save_json(DATA_DIR . '/users_backup.json', $users);
    }
}

save_json(STATES_FILE, $states);

process_pending_orders();
process_pending_channel_orders();
schedule_penalty_checks();
schedule_promocode_updates();
?>