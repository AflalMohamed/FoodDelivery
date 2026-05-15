<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

require '../vendor/phpmailer/src/Exception.php';
require '../vendor/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";
$current_file = basename(__FILE__);

// --- LOGIC: Handle Delete ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($conn->prepare("DELETE FROM users WHERE id = ? AND role = 'rider'")->execute([$del_id])) {
        header("Location: $current_file?msg=deleted"); exit;
    }
}

// --- LOGIC: Manual Status Toggle ---
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $new_status = $_GET['toggle_status'] === 'active' ? 'active' : 'deactive';
    $rider_id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'rider'");
    if ($stmt->execute([$new_status, $rider_id])) {
        header("Location: $current_file?msg=status_updated"); exit;
    }
}

// --- LOGIC: Registration or Update ---
if (isset($_POST['save_rider'])) {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $rider_id = isset($_POST['rider_id']) ? (int)$_POST['rider_id'] : 0;

    if ($rider_id > 0) {
        // UPDATE LOGIC
        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'rider'";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$name, $email, $phone, $rider_id])) {
            $message = "rider_updated";
        }
    } else {
        // NEW REGISTRATION LOGIC
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $v_code = bin2hex(random_bytes(16));

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $message = "email_exists";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, verification_code, is_verified, status) VALUES (?, ?, ?, ?, 'rider', ?, 0, 'deactive')");
            if ($stmt->execute([$name, $email, $phone, $password, $v_code])) {
                // Email sending part...
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; 
                    $mail->SMTPAuth = true;
                    $mail->Username = 'aflaltest@gmail.com'; 
                    $mail->Password = 'vvskkurxcywvqipu';   
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('no-reply@townfood.com', 'TownFood Fleet');
                    $mail->addAddress($email, $name);
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Rider Account';
                    $mail->Body = "<h2>Welcome $name!</h2><p>Admin invited you. Verify here:</p><a href='#'>VERIFY ACCOUNT</a>";
                    $mail->send();
                    $message = "verification_sent";
                } catch (Exception $e) { $message = "mail_error"; }
            }
        }
    }
}

// Fetch Riders
$query = "SELECT * FROM users WHERE role = 'rider' ORDER BY id DESC";
$riders = $conn->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Control | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        @media (max-width: 1024px) { .desktop-table { display: none; } }
        @media (min-width: 1025px) { .mobile-cards { display: none; } }
    </style>
</head>
<body class="text-slate-900">

    <?php include 'includes/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen">
        <main class="p-4 md:p-8">
            
            <header class="mb-8 space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight">Fleet <span class="text-orange-500">Control</span></h1>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Manage your delivery squad</p>
                    </div>
                </div>
                
                <div class="relative max-w-md">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="riderSearch" onkeyup="filterRiders()" placeholder="Search Name or Email..." 
                           class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-slate-200 outline-none focus:ring-2 focus:ring-orange-500/20 shadow-sm text-xs font-bold transition-all">
                </div>
            </header>

            <?php if($message): ?>
                <div class="bg-slate-900 text-white p-4 mb-6 rounded-2xl text-[10px] font-black uppercase flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-orange-500"></i> <?= str_replace('_', ' ', $message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <div class="xl:col-span-4">
                    <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-8">
                        <h2 id="formTitle" class="text-xs font-black uppercase mb-6 tracking-tighter text-slate-400 italic">Register Rider</h2>
                        <form method="POST" id="riderForm" class="space-y-4">
                            <input type="hidden" name="rider_id" id="rider_id">
                            <input type="text" name="name" id="f_name" placeholder="Full Name" required class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none text-xs font-bold focus:ring-2 focus:ring-orange-500/20 transition-all">
                            <input type="email" name="email" id="f_email" placeholder="Email Address" required class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none text-xs font-bold focus:ring-2 focus:ring-orange-500/20 transition-all">
                            <input type="text" name="phone" id="f_phone" placeholder="Phone Number" required class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none text-xs font-bold focus:ring-2 focus:ring-orange-500/20 transition-all">
                            <div id="passField">
                                <input type="password" name="password" id="f_pass" placeholder="Set Password" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none text-xs font-bold focus:ring-2 focus:ring-orange-500/20 transition-all">
                            </div>
                            <button type="submit" name="save_rider" id="submitBtn" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-500 transition-all shadow-lg shadow-slate-200">Send Invite</button>
                            <button type="button" onclick="resetForm()" id="cancelBtn" class="hidden w-full bg-slate-100 text-slate-500 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest">Cancel Edit</button>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-8">
                    
                    <div class="desktop-table bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400">
                                <tr>
                                    <th class="p-6">Identity</th>
                                    <th class="p-6 text-center">Status</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($riders as $r): 
                                    $isVerified = $r['is_verified'] == 1;
                                    $active = $r['status'] == 'active';
                                ?>
                                <tr class="rider-item border-b border-slate-50 hover:bg-slate-50/50 transition-all" data-search="<?= strtolower($r['name'].' '.$r['email']) ?>">
                                    <td class="p-6">
                                        <p class="text-xs font-black uppercase"><?= htmlspecialchars($r['name']) ?></p>
                                        <p class="text-[9px] text-slate-400 font-bold"><?= htmlspecialchars($r['email']) ?></p>
                                    </td>
                                    <td class="p-6 text-center">
                                        <?php if(!$isVerified): ?>
                                            <span class="bg-orange-100 text-orange-600 px-3 py-1.5 rounded-full text-[8px] font-black uppercase italic">Pending</span>
                                        <?php else: ?>
                                            <a href="?toggle_status=<?= $active ? 'deactive' : 'active' ?>&id=<?= $r['id'] ?>" 
                                               class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase flex items-center justify-center gap-2 mx-auto w-fit transition-all <?= $active ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?>">
                                                <i class="fa-solid <?= $active ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                <?= $active ? 'Active' : 'Deactive' ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-6 text-right space-x-2">
                                        <button onclick="editRider(<?= htmlspecialchars(json_encode($r)) ?>)" class="w-9 h-9 bg-slate-50 text-slate-400 rounded-xl hover:bg-orange-50 hover:text-orange-500 transition-all">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $r['id'] ?>)" class="w-9 h-9 bg-slate-50 text-slate-400 rounded-xl hover:bg-red-50 hover:text-red-500 transition-all">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-cards space-y-4">
                        <?php foreach($riders as $r): 
                             $isVerified = $r['is_verified'] == 1;
                             $active = $r['status'] == 'active';
                        ?>
                        <div class="rider-item bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm" data-search="<?= strtolower($r['name'].' '.$r['email']) ?>">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-sm font-black uppercase text-slate-800 tracking-tight"><?= htmlspecialchars($r['name']) ?></h3>
                                    <p class="text-[10px] text-slate-400 font-bold"><?= htmlspecialchars($r['email']) ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editRider(<?= htmlspecialchars(json_encode($r)) ?>)" class="text-slate-300 hover:text-orange-500"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button onclick="confirmDelete(<?= $r['id'] ?>)" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                                <span class="text-[9px] font-black uppercase text-slate-300 italic">Permissions</span>
                                <?php if(!$isVerified): ?>
                                    <span class="text-orange-500 text-[9px] font-black uppercase">Verify Required</span>
                                <?php else: ?>
                                    <a href="?toggle_status=<?= $active ? 'deactive' : 'active' ?>&id=<?= $r['id'] ?>" 
                                       class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase <?= $active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400' ?>">
                                        <i class="fa-solid <?= $active ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                        <?= $active ? 'Enabled' : 'Disabled' ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function filterRiders() {
            let input = document.getElementById('riderSearch').value.toLowerCase();
            document.querySelectorAll('.rider-item').forEach(item => {
                let text = item.getAttribute('data-search');
                item.style.display = text.includes(input) ? '' : 'none';
            });
        }

        function editRider(rider) {
            document.getElementById('formTitle').innerText = "Edit Rider Profile";
            document.getElementById('rider_id').value = rider.id;
            document.getElementById('f_name').value = rider.name;
            document.getElementById('f_email').value = rider.email;
            document.getElementById('f_phone').value = rider.phone;
            
            document.getElementById('passField').classList.add('hidden');
            document.getElementById('f_pass').removeAttribute('required');
            
            document.getElementById('submitBtn').innerText = "Update Profile";
            document.getElementById('submitBtn').classList.replace('bg-slate-900', 'bg-orange-500');
            document.getElementById('cancelBtn').classList.remove('hidden');
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('formTitle').innerText = "Register Rider";
            document.getElementById('rider_id').value = "";
            document.getElementById('riderForm').reset();
            
            document.getElementById('passField').classList.remove('hidden');
            document.getElementById('submitBtn').innerText = "Send Invite";
            document.getElementById('submitBtn').classList.replace('bg-orange-500', 'bg-slate-900');
            document.getElementById('cancelBtn').classList.add('hidden');
        }

        function confirmDelete(id) {
            if(confirm("Permanently remove this rider?")) {
                window.location.href = `?delete_id=${id}`;
            }
        }
    </script>
</body>
</html>