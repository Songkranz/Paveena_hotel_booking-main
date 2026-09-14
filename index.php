<?php
session_start();
require_once 'config/db_connect.php';

$types = $pdo->query("SELECT rt.*,
                             (SELECT COUNT(*) FROM rooms r
                              WHERE r.room_type_id = rt.id
                              AND r.status <> 'maintenance') AS room_count,
                             (SELECT COALESCE(r.image, r.image_2, r.image_3)
                              FROM rooms r
                              WHERE r.room_type_id = rt.id
                              AND r.status <> 'maintenance'
                              AND COALESCE(r.image, r.image_2, r.image_3) IS NOT NULL
                              ORDER BY r.id ASC LIMIT 1) AS cover_image
                      FROM room_types rt
                      ORDER BY rt.price_per_night ASC")->fetchAll(PDO::FETCH_ASSOC);

$is_logged_in = isset($_SESSION['user_id']);
$is_customer = $is_logged_in && $_SESSION['role'] === 'customer';
$home = null;
if ($is_logged_in) {
    $home = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php'
          : ($_SESSION['role'] === 'staff' ? 'staff/dashboard.php' : 'customer/dashboard.php');
}
$today = date('Y-m-d');
$room_card_images = [
    1 => 'assets/room-single.png',
    2 => 'assets/room-double.png',
    3 => 'assets/room-family.png',
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paveena Hotel — จองห้องพักออนไลน์</title>
    <link rel="stylesheet" href="assets/site.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="index.php">
                <span class="brand-mark">PH</span>
                <span>PAVEENA HOTEL</span>
            </a>
            <div class="header-account">
                <?php if ($is_logged_in): ?>
                    <span>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <a class="button" href="<?= $home ?>">หน้าจัดการ</a>
                    <a href="customer/logout.php">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="customer/login.php">เข้าสู่ระบบ</a>
                    <a class="button" href="customer/register.php">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="site-nav" aria-label="เมนูหลัก">
            <a class="active" href="#home">หน้าแรก</a>
            <a href="#rooms">ห้องพัก</a>
            <a href="#booking-steps">วิธีการจอง</a>
            <a href="#policy">นโยบาย</a>
        </nav>
    </header>

    <main>
        <section class="home-hero" id="home">
            <div class="hero-copy">
                <p class="eyebrow">พักผ่อนในแบบที่เป็นคุณ</p>
                <h1>PAVEENA HOTEL</h1>
                <p>ความสบายที่เรียบง่าย พร้อมให้คุณเริ่มต้นวันพักผ่อนที่น่าจดจำ</p>
            </div>

            <form class="booking-bar" action="<?= $is_customer ? 'customer/search_rooms.php' : 'customer/login.php' ?>"
                  method="<?= $is_customer ? 'POST' : 'GET' ?>">
                <div class="field">
                    <label for="checkin_date">วันที่เข้าพัก</label>
                    <input type="date" id="checkin_date" name="checkin_date" min="<?= $today ?>" required>
                </div>
                <div class="field">
                    <label for="checkout_date">วันที่ออก</label>
                    <input type="date" id="checkout_date" name="checkout_date" min="<?= $today ?>" required>
                </div>
                <button class="button button-aqua" type="submit">
                    <?= $is_customer ? 'ค้นหาห้องว่าง' : 'เข้าสู่ระบบเพื่อจอง' ?>
                </button>
            </form>
        </section>

        <section class="intro-strip">
            <div class="intro-title">
                <h2>PAVEENA HOTEL</h2>
                <div class="stars" aria-label="ระดับความประทับใจ 5 ดาว">★★★★★</div>
            </div>
            <p>พื้นที่พักผ่อนที่ออกแบบให้จองได้ง่าย ตั้งแต่เลือกวันเข้าพัก เลือกห้องและบริการเสริม
               ไปจนถึงแนบหลักฐานการชำระเงินและติดตามสถานะได้ในที่เดียว</p>
        </section>

        <section class="section section-soft" id="rooms">
            <div class="section-heading">
                <div>
                    <p class="eyebrow" style="color:var(--aqua);">เลือกพื้นที่ของคุณ</p>
                    <h2>ห้องพัก</h2>
                </div>
                <a class="text-link" href="<?= $is_customer ? 'customer/search_rooms.php' : 'customer/login.php' ?>">ดูห้องว่างทั้งหมด →</a>
            </div>

            <?php if (!$types): ?>
                <div class="empty-state">ยังไม่มีข้อมูลห้องพักในระบบ</div>
            <?php else: ?>
                <div class="room-grid">
                    <?php foreach ($types as $type): ?>
                        <?php
                        $card_image = $room_card_images[(int)$type['id']]
                                   ?? ($type['cover_image'] ? 'assets/uploads/rooms/' . $type['cover_image'] : 'assets/hotel-hero.png');
                        ?>
                        <article class="room-card">
                            <img class="room-card-image" src="<?= htmlspecialchars($card_image) ?>"
                                 alt="<?= htmlspecialchars($type['type_name']) ?>">
                            <div class="room-card-body">
                                <h3><?= htmlspecialchars($type['type_name']) ?></h3>
                                <p><?= htmlspecialchars($type['description']) ?></p>
                                <div class="room-meta">
                                    <span>พักได้ <?= (int)$type['max_guests'] ?> คน<br>พร้อมใช้ <?= (int)$type['room_count'] ?> ห้อง</span>
                                    <span class="room-price"><?= number_format($type['price_per_night'], 0) ?><small> บาท/คืน</small></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="section" id="booking-steps">
            <div class="section-heading">
                <div>
                    <p class="eyebrow" style="color:var(--aqua);">เรียบง่ายทุกขั้นตอน</p>
                    <h2>วิธีการจอง</h2>
                </div>
            </div>
            <div class="steps-grid">
                <div class="step"><h3>สมัครสมาชิก</h3><p>สร้างบัญชีและเข้าสู่ระบบลูกค้า</p></div>
                <div class="step"><h3>ค้นหาห้อง</h3><p>เลือกวันเข้าพักและวันออก</p></div>
                <div class="step"><h3>ยืนยันการจอง</h3><p>เลือกห้องและบริการเสริมที่ต้องการ</p></div>
                <div class="step"><h3>แนบสลิป</h3><p>อัปโหลดหลักฐานการโอนในระบบ</p></div>
                <div class="step"><h3>รอยืนยัน</h3><p>พนักงานตรวจสอบและยืนยันรายการ</p></div>
            </div>

            <div class="policy-box" id="policy">
                <span class="policy-icon">ⓘ</span>
                <div>
                    <strong>นโยบายการยกเลิก</strong>
                    <p>เมื่อชำระเงินและได้รับการยืนยันแล้ว หากยกเลิกภายหลัง โรงแรมขอสงวนสิทธิ์ไม่คืนเงินทุกกรณี</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span>© <?= date('Y') ?> Paveena Hotel</span>
        <span>ระบบจองห้องพักออนไลน์</span>
    </footer>
</div>

<script>
const checkinInput = document.getElementById('checkin_date');
const checkoutInput = document.getElementById('checkout_date');
checkinInput.addEventListener('change', () => {
    checkoutInput.min = checkinInput.value || '<?= $today ?>';
    if (checkoutInput.value && checkoutInput.value <= checkinInput.value) {
        checkoutInput.value = '';
    }
});
</script>
</body>
</html>
