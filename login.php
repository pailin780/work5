<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// ถ้าล็อกอินแล้วให้ redirect ไป dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'inactive') {
                $error = 'บัญชีของคุณถูกระงับการใช้งาน';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                setAlert('เข้าสู่ระบบสำเร็จ!', 'success');
                redirect('dashboard.php');
            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ไม่พบชื่อผู้ใช้นี้ในระบบ';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการผู้ใช้</title>

    <!-- ใช้ไฟล์ CSS โทนม่วงพาสเทล -->
    <link rel="stylesheet" href="css/style.css">

<style>
    /* โทนสีแดงเลือดหมู */
    body{
        background: linear-gradient(135deg, #7b1e3b 0%, #b11226 100%);
    }

    .card-header{
        background: linear-gradient(135deg, #7b1e3b 0%, #b11226 100%);
        color:#fff;
    }

    .btn-primary{
        background: linear-gradient(135deg, #9b2339 0%, #b11226 100%);
        color:#fff;
        border:none;
    }

    .btn-primary:hover{
        box-shadow:0 10px 20px rgba(177,18,38,.4);
        transform: translateY(-1px);
    }

    .link{
        color:#b11226;
    }

    hr{
        border-top:1px solid #e5a3ad !important;
    }

    .demo-box{
        background:#ffe5ea;
        border:1px dashed #b11226;
        color:#7b1e3b;
    }
</style>

</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>🔐 เข้าสู่ระบบ</h2>
                <p>ยินดีต้อนรับกลับมา! กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
            </div>

            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                $alert = getAlert();
                if ($alert): 
                ?>
                    <div class="alert alert-<?php echo $alert['type']; ?>">
                        <?php echo $alert['message']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้หรืออีเมล</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            placeholder="กรอกชื่อผู้ใช้หรืออีเมล"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">รหัสผ่าน</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="กรอกรหัสผ่าน"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                    </div>
                    
                    <div class="text-center">
                        <p>ยังไม่มีบัญชี? <a href="register.php" class="link">สมัครสมาชิก</a></p>
                    </div>
                </form>
                
                <hr style="margin: 30px 0;">

                <div class="demo-box" style="padding:15px; border-radius:10px; font-size:13px;">
                    <strong>🔑 บัญชีทดสอบ:</strong><br>
                    Admin: admin / password123<br>
                    User: user01 / password123<br>
                    Customer: customer01 / password123<br>
                    Employee: employee01 / password123
                </div>

            </div>
        </div>
    </div>
</body>
</html>
