<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$rooms = [];
$searched = false;
$today = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $checkin = $_POST['checkin_date'];
    $checkout = $_POST['checkout_date'];
    $searched = true;

    $sql = "SELECT rooms.id, rooms.room_number, rooms.image, rooms.image_2, rooms.image_3,
                   room_types.type_name, room_types.price_per_night,
                   room_types.max_guests, room_types.description
            FROM rooms
            JOIN room_types ON rooms.room_type_id = room_types.id
            WHERE rooms.status = 'available'
            AND rooms.id NOT IN (
                SELECT room_id FROM bookings
                WHERE status IN ('pending', 'confirmed', 'checked_in')
                AND checkin_date < ?
                AND checkout_date > ?
            )
            ORDER BY room_types.price_per_night ASC, rooms.room_number ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$checkout, $checkin]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ค้นหาห้องพัก — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="../index.php">
                <span class="brand-mark">PH</span>
                <span>PAVEENA HOTEL</span>
            </a>
            <div class="header-account">
                <span>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a class="button" href="dashboard.php">บัญชีของฉัน</a>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </div>
        <nav class="site-nav" aria-label="เมนูหลัก">
            <a href="../index.php">หน้าแรก</a>
            <a class="active" href="search_rooms.php">ค้นหาห้อง</a>
            <a href="my_bookings.php">การจองของฉัน</a>
        </nav>
    </header>

    <main>
        <section class="search-hero">
            <p class="eyebrow">วางแผนวันพักผ่อน</p>
            <h1>ค้นหาห้องว่าง</h1>
            <p>เลือกช่วงเวลาที่ต้องการ แล้วพบกับห้องพักที่พร้อมสำหรับคุณ</p>
        </section>

        <div class="search-panel">
            <form class="booking-bar" method="POST">
                <div class="field">
                    <label for="checkin_date">วันที่เข้าพัก</label>
                    <input type="date" id="checkin_date" name="checkin_date" min="<?= $today ?>" required
                           value="<?= htmlspecialchars($_POST['checkin_date'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="checkout_date">วันที่ออก</label>
                    <input type="date" id="checkout_date" name="checkout_date" min="<?= $today ?>" required
                           value="<?= htmlspecialchars($_POST['checkout_date'] ?? '') ?>">
                </div>
                <button class="button button-aqua" type="submit">ค้นหาห้องว่าง</button>
            </form>
        </div>

        <section class="results-wrap">
            <div class="section-heading">
                <div>
                    <p class="eyebrow" style="color:var(--aqua);">Paveena Hotel</p>
                    <h2><?= $searched ? 'ผลการค้นหา' : 'ห้องที่เหมาะกับคุณ' ?></h2>
                </div>
                <?php if ($searched): ?>
                    <p>พบ <?= count($rooms) ?> ห้อง</p>
                <?php endif; ?>
            </div>

            <?php if (!$searched): ?>
                <div class="empty-state">เลือกวันที่เข้าพักและวันที่ออกเพื่อเริ่มค้นหาห้องว่าง</div>
            <?php elseif (!$rooms): ?>
                <div class="empty-state">ไม่มีห้องว่างในช่วงวันที่เลือก ลองเปลี่ยนวันเข้าพักอีกครั้ง</div>
            <?php else: ?>
                <div class="results-grid">
                    <?php foreach ($rooms as $room): ?>
                        <?php
                        $room_images = array_values(array_filter([
                            $room['image'], $room['image_2'], $room['image_3']
                        ]));
                        ?>
                        <article class="result-card">
                            <?php if ($room_images): ?>
                                <div class="room-slider" data-slide-index="0">
                                    <?php foreach ($room_images as $index => $image_name): ?>
                                        <img src="../assets/uploads/rooms/<?= htmlspecialchars($image_name) ?>"
                                             alt="รูปห้อง <?= htmlspecialchars($room['room_number']) ?> รูปที่ <?= $index + 1 ?>"
                                             class="room-slide <?= $index === 0 ? 'active' : '' ?>">
                                    <?php endforeach; ?>
                                    <?php if (count($room_images) > 1): ?>
                                        <button type="button" class="slider-btn slider-prev"
                                                onclick="moveSlide(this, -1)" aria-label="รูปก่อนหน้า">&#8249;</button>
                                        <button type="button" class="slider-btn slider-next"
                                                onclick="moveSlide(this, 1)" aria-label="รูปถัดไป">&#8250;</button>
                                        <span class="slider-counter">1 / <?= count($room_images) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-image">ยังไม่มีรูปห้อง</div>
                            <?php endif; ?>

                            <div class="result-card-body">
                                <p class="eyebrow" style="color:var(--aqua);">ห้องหมายเลข <?= htmlspecialchars($room['room_number']) ?></p>
                                <h3 class="result-title"><?= htmlspecialchars($room['type_name']) ?></h3>
                                <p class="result-description"><?= htmlspecialchars($room['description']) ?></p>
                                <div class="result-actions">
                                    <div>
                                        <span class="room-price"><?= number_format($room['price_per_night'], 0) ?></span>
                                        <small>บาท / คืน · พักได้ <?= (int)$room['max_guests'] ?> คน</small>
                                    </div>
                                    <form method="POST" action="book_room.php">
                                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                        <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($_POST['checkin_date']) ?>">
                                        <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($_POST['checkout_date']) ?>">
                                        <button class="button" type="submit">จองห้องนี้</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <span>© <?= date('Y') ?> Paveena Hotel</span>
        <span>จองง่าย ชำระเงินสะดวก ยืนยันรวดเร็ว</span>
    </footer>
</div>

<script>
function moveSlide(button, step) {
    const slider = button.closest('.room-slider');
    const slides = slider.querySelectorAll('.room-slide');
    let index = Number(slider.dataset.slideIndex || 0);

    slides[index].classList.remove('active');
    index = (index + step + slides.length) % slides.length;
    slides[index].classList.add('active');
    slider.dataset.slideIndex = index;

    const counter = slider.querySelector('.slider-counter');
    if (counter) {
        counter.textContent = (index + 1) + ' / ' + slides.length;
    }
}

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
