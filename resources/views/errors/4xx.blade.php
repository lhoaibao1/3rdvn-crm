<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>400 — 3RD-VN System Status</title>

<style>
/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --bg: #05060b;
    --bg2: #0b0d15;

    --white: #f7f8ff;
    --muted: #858ba0;
    --dim: #4b5164;

    --accent: #ff4d78;
    --accent2: #ff8da8;
    --cyan: #55ddff;
    --purple: #9d7cff;
    --green: #67e6a1;
    --yellow: #ffd166;

    --glass: rgba(255,255,255,.045);
    --glass2: rgba(255,255,255,.025);
    --line: rgba(255,255,255,.09);
}


/* =========================================================
   BASE
========================================================= */

html,
body {
    min-height: 100%;
}

body {
    min-height: 100vh;
    overflow-x: hidden;

    color: var(--white);

    background:
        radial-gradient(
            circle at 50% 42%,
            #191c2d 0%,
            #0c0f18 34%,
            #05060b 75%
        );

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

button {
    font: inherit;
}


/* =========================================================
   NOISE
========================================================= */

.noise {
    position: fixed;
    inset: 0;

    z-index: 100;

    pointer-events: none;

    opacity: .035;

    background-image:
        url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.7'/%3E%3C/svg%3E");

    mix-blend-mode: screen;
}


/* =========================================================
   BACKGROUND
========================================================= */

.background {
    position: fixed;
    inset: 0;

    overflow: hidden;

    pointer-events: none;
}

.ambient {
    position: absolute;

    width: 600px;
    height: 600px;

    border-radius: 50%;

    filter: blur(130px);

    opacity: .12;

    animation:
        ambientMove 12s ease-in-out infinite;
}

.ambient.one {
    left: -220px;
    top: -160px;

    background: #ff315f;
}

.ambient.two {
    right: -220px;
    bottom: -180px;

    background: #684fff;

    animation-delay: -5s;
}

.ambient.three {
    left: 45%;
    top: 25%;

    width: 350px;
    height: 350px;

    background: #27cfff;

    opacity: .055;

    animation-delay: -8s;
}


/* =========================================================
   GRID
========================================================= */

.grid {
    position: absolute;
    inset: -50%;

    background-image:
        linear-gradient(
            rgba(255,255,255,.028) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.028) 1px,
            transparent 1px
        );

    background-size: 55px 55px;

    transform:
        perspective(900px)
        rotateX(65deg)
        translateY(15%);

    transform-origin: center center;

    mask-image:
        radial-gradient(
            ellipse at center,
            black 15%,
            transparent 72%
        );

    animation:
        gridMove 18s linear infinite;
}


/* =========================================================
   STAR PARTICLES
========================================================= */

.stars {
    position: absolute;
    inset: 0;
}

.star {
    position: absolute;

    width: var(--size);
    height: var(--size);

    border-radius: 50%;

    background: white;

    box-shadow:
        0 0 10px white,
        0 0 18px var(--cyan);

    opacity: .25;

    animation:
        starFloat var(--speed) ease-in-out infinite;
}

.star:nth-child(1)  { left: 5%;  top: 20%; --size:2px; --speed:5s; }
.star:nth-child(2)  { left: 12%; top: 67%; --size:3px; --speed:7s; }
.star:nth-child(3)  { left: 21%; top: 14%; --size:2px; --speed:6s; }
.star:nth-child(4)  { left: 29%; top: 82%; --size:2px; --speed:8s; }
.star:nth-child(5)  { left: 37%; top: 8%;  --size:3px; --speed:5s; }
.star:nth-child(6)  { left: 45%; top: 90%; --size:2px; --speed:7s; }
.star:nth-child(7)  { left: 54%; top: 12%; --size:2px; --speed:6s; }
.star:nth-child(8)  { left: 63%; top: 78%; --size:3px; --speed:8s; }
.star:nth-child(9)  { left: 72%; top: 17%; --size:2px; --speed:5s; }
.star:nth-child(10) { left: 82%; top: 70%; --size:2px; --speed:7s; }
.star:nth-child(11) { left: 91%; top: 25%; --size:3px; --speed:6s; }
.star:nth-child(12) { left: 88%; top: 88%; --size:2px; --speed:8s; }


/* =========================================================
   FLOATING LOGS
========================================================= */

.log {
    position: absolute;

    padding: 7px 10px;

    border:
        1px solid
        rgba(255,255,255,.06);

    border-radius: 7px;

    background:
        rgba(5,7,13,.55);

    backdrop-filter: blur(12px);

    color: #414758;

    font:
        9px "Courier New",
        monospace;

    letter-spacing: 1px;

    animation:
        logFloat var(--speed) ease-in-out infinite;
}

.log::before {
    content: "◆";

    margin-right: 6px;

    color: var(--accent);

    text-shadow:
        0 0 8px var(--accent);
}

.log.l1 {
    left: 4%;
    top: 23%;
    --speed: 6s;
}

.log.l2 {
    right: 5%;
    top: 20%;
    --speed: 8s;
}

.log.l3 {
    left: 7%;
    bottom: 25%;
    --speed: 7s;
}

.log.l4 {
    right: 7%;
    bottom: 22%;
    --speed: 5s;
}

.log.l5 {
    left: 21%;
    top: 10%;
    --speed: 9s;
}

.log.l6 {
    right: 20%;
    top: 11%;
    --speed: 6s;
}


/* =========================================================
   PAGE
========================================================= */

.page {
    position: relative;
    z-index: 10;

    width: min(1220px, calc(100% - 32px));

    min-height: 100vh;

    margin: auto;

    padding:
        35px 0 45px;
}


/* =========================================================
   HEADER
========================================================= */

.header {
    text-align: center;

    margin-bottom: 25px;
}

.status-pill {
    display: inline-flex;

    align-items: center;
    gap: 8px;

    padding: 7px 12px;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius: 999px;

    background:
        rgba(255,255,255,.025);

    backdrop-filter: blur(12px);

    color: #777e91;

    font:
        9px "Courier New",
        monospace;

    letter-spacing: 2px;

    box-shadow:
        0 0 30px rgba(255,255,255,.015);
}

.status-light {
    width: 6px;
    height: 6px;

    border-radius: 50%;

    background: var(--accent);

    box-shadow:
        0 0 7px var(--accent),
        0 0 15px var(--accent);

    animation:
        pulse 1.5s infinite;
}

.header h1 {
    margin-top: 13px;

    font-size:
        clamp(30px, 4vw, 46px);

    line-height: 1;

    letter-spacing: -2px;

    text-shadow:
        0 0 30px rgba(255,255,255,.08);
}

.header p {
    margin-top: 10px;

    color: var(--muted);

    font-size: 12px;
}


/* =========================================================
   ERROR SELECTOR
========================================================= */

.selector {
    display: grid;

    grid-template-columns:
        repeat(8, minmax(75px, 1fr));

    gap: 7px;

    margin-bottom: 18px;
}

.selector label {
    position: relative;

    cursor: pointer;

    min-height: 52px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 3px;

    overflow: hidden;

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius: 9px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.045),
            rgba(255,255,255,.015)
        );

    color: #5f6678;

    transition:
        .25s ease;

    user-select: none;
}

.selector label::before {
    content: "";

    position: absolute;

    width: 80px;
    height: 80px;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%,-50%);

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(255,255,255,.08),
            transparent 65%
        );

    opacity: 0;

    transition: .25s;
}

.selector label:hover {
    transform:
        translateY(-3px);

    color: #fff;

    border-color:
        rgba(255,255,255,.16);

    box-shadow:
        0 10px 30px rgba(0,0,0,.25);
}

.selector label:hover::before {
    opacity: 1;
}

.selector .number {
    position: relative;

    font:
        700 11px
        "Courier New",
        monospace;
}

.selector .name {
    position: relative;

    font-size: 7px;

    letter-spacing: 1px;

    opacity: .65;
}


/* =========================================================
   ACTIVE SELECTOR
========================================================= */

#e400:checked ~ .page .selector label[for="e400"],
#e401:checked ~ .page .selector label[for="e401"],
#e402:checked ~ .page .selector label[for="e402"],
#e403:checked ~ .page .selector label[for="e403"],
#e404:checked ~ .page .selector label[for="e404"],
#e405:checked ~ .page .selector label[for="e405"],
#e408:checked ~ .page .selector label[for="e408"],
#e409:checked ~ .page .selector label[for="e409"],
#e410:checked ~ .page .selector label[for="e410"],
#e429:checked ~ .page .selector label[for="e429"],
#e500:checked ~ .page .selector label[for="e500"],
#e501:checked ~ .page .selector label[for="e501"],
#e502:checked ~ .page .selector label[for="e502"],
#e503:checked ~ .page .selector label[for="e503"],
#e504:checked ~ .page .selector label[for="e504"] {

    color: #fff;

    border-color:
        rgba(255,77,120,.7);

    background:
        linear-gradient(
            145deg,
            rgba(255,77,120,.13),
            rgba(255,255,255,.025)
        );

    box-shadow:
        0 0 25px rgba(255,77,120,.09),
        inset 0 0 20px rgba(255,77,120,.025);
}


/* =========================================================
   MAIN CARD
========================================================= */

.dashboard {
    position: relative;

    min-height: 600px;

    overflow: hidden;

    border:
        1px solid
        rgba(255,255,255,.09);

    border-radius: 26px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.055),
            rgba(255,255,255,.015)
        );

    backdrop-filter:
        blur(25px);

    box-shadow:
        0 45px 100px rgba(0,0,0,.5),
        inset 0 1px rgba(255,255,255,.055);
}


/* top highlight */

.dashboard::before {
    content: "";

    position: absolute;

    top: 0;
    left: 8%;

    width: 84%;
    height: 1px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.3),
            transparent
        );

    filter:
        blur(.5px);
}


/* scanline */

.dashboard::after {
    content: "";

    position: absolute;

    inset: 0;

    pointer-events: none;

    background:
        repeating-linear-gradient(
            0deg,
            transparent 0,
            transparent 4px,
            rgba(255,255,255,.012) 5px
        );

    opacity: .5;
}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {
    position: relative;

    z-index: 20;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 17px;

    border-bottom:
        1px solid
        rgba(255,255,255,.065);

    background:
        rgba(0,0,0,.12);
}

.window-dots {
    display: flex;

    gap: 6px;
}

.window-dots span {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #3a3f4d;
}

.window-name {
    color: #555c6d;

    font:
        9px "Courier New",
        monospace;

    letter-spacing: 1px;
}

.window-status {
    color: #525969;

    font:
        8px "Courier New",
        monospace;

    letter-spacing: 1px;
}

.window-status span {
    color: var(--accent);

    text-shadow:
        0 0 8px var(--accent);
}


/* =========================================================
   CONTENT
========================================================= */

.core {
    position: relative;

    min-height: 555px;

    display: grid;

    grid-template-columns:
        1fr
        minmax(350px, 1.2fr)
        1fr;

    align-items: center;

    padding: 45px 45px 55px;
}


/* =========================================================
   SIDE PANELS
========================================================= */

.side {
    position: relative;

    z-index: 10;

    display: flex;
    flex-direction: column;

    gap: 9px;
}

.side.right {
    align-items: flex-end;
}

.info-card {
    width: min(220px, 100%);

    padding: 13px 14px;

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius: 11px;

    background:
        rgba(255,255,255,.025);

    backdrop-filter: blur(10px);

    transition:
        .25s ease;
}

.info-card:hover {
    transform:
        translateY(-4px);

    border-color:
        rgba(255,255,255,.13);

    box-shadow:
        0 15px 35px rgba(0,0,0,.2);
}

.card-label {
    color: #555d6f;

    font:
        8px "Courier New",
        monospace;

    letter-spacing: 1.5px;
}

.card-value {
    margin-top: 6px;

    color: #dfe2ea;

    font:
        10px "Courier New",
        monospace;
}

.card-value.accent {
    color: var(--accent);

    text-shadow:
        0 0 12px rgba(255,77,120,.3);
}

.card-value.green {
    color: var(--green);
}

.card-value.cyan {
    color: var(--cyan);
}


/* =========================================================
   CENTER
========================================================= */

.center {
    position: relative;

    display: flex;
    flex-direction: column;

    align-items: center;

    text-align: center;

    z-index: 10;
}


/* =========================================================
   HOLOGRAM
========================================================= */

.hologram {
    position: relative;

    width: 230px;
    height: 230px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 18px;
}


/* core glow */

.core-glow {
    position: absolute;

    width: 125px;
    height: 125px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(255,77,120,.22),
            rgba(255,77,120,.04) 45%,
            transparent 70%
        );

    filter:
        blur(5px);

    animation:
        corePulse 3s ease-in-out infinite;
}


/* rings */

.ring {
    position: absolute;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.09);
}

.ring.r1 {
    width: 150px;
    height: 150px;

    border-top-color: var(--accent);

    animation:
        rotate 8s linear infinite;
}

.ring.r2 {
    width: 185px;
    height: 185px;

    border-right-color: var(--cyan);

    border-left-color:
        rgba(255,255,255,.025);

    animation:
        rotateReverse 13s linear infinite;
}

.ring.r3 {
    width: 220px;
    height: 220px;

    border-bottom-color: var(--purple);

    border-top-color:
        rgba(255,255,255,.025);

    animation:
        rotate 20s linear infinite;
}


/* ring dots */

.ring.r1::after,
.ring.r2::after,
.ring.r3::after {
    content: "";

    position: absolute;

    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: currentColor;

    box-shadow:
        0 0 12px currentColor;
}

.ring.r1::after {
    right: 4px;
    top: 27px;

    color: var(--accent);
}

.ring.r2::after {
    left: 14px;
    bottom: 25px;

    color: var(--cyan);
}

.ring.r3::after {
    right: 30px;
    bottom: 5px;

    color: var(--purple);
}


/* central cube */

.holo-core {
    position: relative;

    width: 94px;
    height: 94px;

    display: flex;
    align-items: center;
    justify-content: center;

    border:
        1px solid
        rgba(255,255,255,.16);

    border-radius: 24px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.11),
            rgba(255,255,255,.025)
        );

    backdrop-filter:
        blur(12px);

    box-shadow:
        0 0 45px rgba(255,77,120,.14),
        inset 0 1px rgba(255,255,255,.18);

    transform:
        rotate(45deg);

    animation:
        cubeFloat 4s ease-in-out infinite;
}

.holo-core-inner {
    transform:
        rotate(-45deg);

    font:
        900 31px Arial,
        sans-serif;

    color: white;

    text-shadow:
        2px 0 var(--cyan),
        -2px 0 var(--accent);

    animation:
        glitch 3s infinite;
}


/* =========================================================
   SPARKLES
========================================================= */

.spark {
    position: absolute;

    width: 5px;
    height: 5px;

    background: white;

    transform: rotate(45deg);

    box-shadow:
        0 0 10px white,
        0 0 20px var(--cyan);

    animation:
        sparkle 2.5s ease-in-out infinite;
}

.spark::before,
.spark::after {
    content: "";

    position: absolute;

    background: inherit;

    box-shadow: inherit;
}

.spark::before {
    width: 1px;
    height: 15px;

    left: 2px;
    top: -5px;
}

.spark::after {
    width: 15px;
    height: 1px;

    left: -5px;
    top: 2px;
}

.s1 {
    top: 25px;
    left: 38px;
}

.s2 {
    top: 75px;
    right: 15px;

    animation-delay: -.8s;
}

.s3 {
    bottom: 20px;
    left: 45px;

    animation-delay: -1.5s;
}

.s4 {
    bottom: 50px;
    right: 32px;

    animation-delay: -.4s;
}


/* =========================================================
   MAIN ERROR TEXT
========================================================= */

.error-code {
    position: relative;

    font:
        900 62px Arial,
        sans-serif;

    letter-spacing: -4px;

    line-height: 1;

    text-shadow:
        3px 0 var(--cyan),
        -3px 0 var(--accent);

    animation:
        glitch 4s infinite;
}

.error-title {
    margin-top: 9px;

    font-size: 21px;

    font-weight: 700;

    letter-spacing: -.6px;
}

.error-description {
    max-width: 470px;

    margin-top: 8px;

    color: var(--muted);

    font-size: 11px;

    line-height: 1.65;
}


/* =========================================================
   TERMINAL
========================================================= */

.terminal {
    width: min(450px, 100%);

    margin-top: 18px;

    padding: 12px 14px;

    text-align: left;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius: 10px;

    background:
        rgba(2,4,8,.55);

    box-shadow:
        inset 0 1px rgba(255,255,255,.025);

    font:
        9px/1.9 "Courier New",
        monospace;

    color: #596173;
}

.terminal-line .prompt {
    color: var(--cyan);
}

.terminal-line .red {
    color: var(--accent);
}

.terminal-line .green {
    color: var(--green);
}

.terminal-line .yellow {
    color: var(--yellow);
}

.cursor {
    display: inline-block;

    width: 5px;
    height: 10px;

    vertical-align: -2px;

    margin-left: 2px;

    background: var(--cyan);

    animation:
        blink .8s infinite;
}


/* =========================================================
   BUTTONS
========================================================= */

.buttons {
    display: flex;

    justify-content: center;

    gap: 8px;

    margin-top: 15px;
}

.btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    padding: 9px 17px;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius: 8px;

    background:
        rgba(255,255,255,.035);

    color: #dfe2e9;

    text-decoration: none;

    font-size: 10px;

    transition: .2s ease;
}

.btn:hover {
    transform:
        translateY(-3px);

    color: white;

    border-color:
        rgba(255,77,120,.7);

    background:
        rgba(255,77,120,.09);

    box-shadow:
        0 10px 30px rgba(255,77,120,.12);
}


/* =========================================================
   CONNECTION BAR
========================================================= */

.connection {
    position: absolute;

    left: 50%;
    bottom: 17px;

    transform:
        translateX(-50%);

    display: flex;

    align-items: center;

    gap: 9px;

    color: #454c5d;

    font:
        8px "Courier New",
        monospace;

    letter-spacing: 1px;
}

.connection-dot {
    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: var(--accent);

    box-shadow:
        0 0 8px var(--accent);
}

.connection-line {
    width: 70px;
    height: 1px;

    background:
        repeating-linear-gradient(
            90deg,
            #4b5262 0 4px,
            transparent 4px 8px
        );

    animation:
        lineMove 1s linear infinite;
}


/* =========================================================
   ERROR CONTENT
========================================================= */

.error-code::before,
.error-title::before,
.error-description::before {
    content: "";
}


/* 400 */

#e400:checked ~ .page .error-code::before {
    content: "400";
}

#e400:checked ~ .page .error-title::before {
    content: "Yêu cầu không hợp lệ";
}

#e400:checked ~ .page .error-description::before {
    content: "Máy chủ không thể hiểu hoặc xử lý yêu cầu được gửi đến.";
}


/* 401 */

#e401:checked ~ .page .error-code::before {
    content: "401";
}

#e401:checked ~ .page .error-title::before {
    content: "Chưa được xác thực";
}

#e401:checked ~ .page .error-description::before {
    content: "Bạn cần đăng nhập hoặc cung cấp thông tin xác thực hợp lệ.";
}


/* 402 */

#e402:checked ~ .page .error-code::before {
    content: "402";
}

#e402:checked ~ .page .error-title::before {
    content: "Yêu cầu thanh toán";
}

#e402:checked ~ .page .error-description::before {
    content: "Tài nguyên này yêu cầu hoàn tất bước thanh toán trước khi tiếp tục.";
}


/* 403 */

#e403:checked ~ .page .error-code::before {
    content: "403";
}

#e403:checked ~ .page .error-title::before {
    content: "Không có quyền truy cập";
}

#e403:checked ~ .page .error-description::before {
    content: "Bạn không có quyền truy cập vào tài nguyên hoặc khu vực này.";
}


/* 404 */

#e404:checked ~ .page .error-code::before {
    content: "404";
}

#e404:checked ~ .page .error-title::before {
    content: "Không tìm thấy đường dẫn";
}

#e404:checked ~ .page .error-description::before {
    content: "Đường dẫn bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.";
}


/* 405 */

#e405:checked ~ .page .error-code::before {
    content: "405";
}

#e405:checked ~ .page .error-title::before {
    content: "Phương thức không được phép";
}

#e405:checked ~ .page .error-description::before {
    content: "Phương thức HTTP được sử dụng không được máy chủ hỗ trợ.";
}


/* 408 */

#e408:checked ~ .page .error-code::before {
    content: "408";
}

#e408:checked ~ .page .error-title::before {
    content: "Yêu cầu đã hết thời gian";
}

#e408:checked ~ .page .error-description::before {
    content: "Máy chủ đã chờ quá lâu nhưng chưa nhận được yêu cầu hoàn chỉnh.";
}


/* 409 */

#e409:checked ~ .page .error-code::before {
    content: "409";
}

#e409:checked ~ .page .error-title::before {
    content: "Xung đột dữ liệu";
}

#e409:checked ~ .page .error-description::before {
    content: "Yêu cầu không thể hoàn tất vì đang xảy ra xung đột với trạng thái hiện tại.";
}


/* 410 */

#e410:checked ~ .page .error-code::before {
    content: "410";
}

#e410:checked ~ .page .error-title::before {
    content: "Tài nguyên đã biến mất";
}

#e410:checked ~ .page .error-description::before {
    content: "Tài nguyên này đã bị xóa và không còn khả dụng tại địa chỉ cũ.";
}


/* 429 */

#e429:checked ~ .page .error-code::before {
    content: "429";
}

#e429:checked ~ .page .error-title::before {
    content: "Quá nhiều yêu cầu";
}

#e429:checked ~ .page .error-description::before {
    content: "Bạn đang gửi yêu cầu quá nhanh. Hãy chờ một chút rồi thử lại.";
}


/* 500 */

#e500:checked ~ .page .error-code::before {
    content: "500";
}

#e500:checked ~ .page .error-title::before {
    content: "Lỗi máy chủ";
}

#e500:checked ~ .page .error-description::before {
    content: "Máy chủ gặp sự cố không mong muốn và không thể hoàn thành yêu cầu.";
}


/* 501 */

#e501:checked ~ .page .error-code::before {
    content: "501";
}

#e501:checked ~ .page .error-title::before {
    content: "Tính năng chưa được hỗ trợ";
}

#e501:checked ~ .page .error-description::before {
    content: "Máy chủ hiện chưa hỗ trợ chức năng mà yêu cầu đang cần.";
}


/* 502 */

#e502:checked ~ .page .error-code::before {
    content: "502";
}

#e502:checked ~ .page .error-title::before {
    content: "Bad Gateway";
}

#e502:checked ~ .page .error-description::before {
    content: "Máy chủ trung gian nhận được phản hồi không hợp lệ từ máy chủ phía sau.";
}


/* 503 */

#e503:checked ~ .page .error-code::before {
    content: "503";
}

#e503:checked ~ .page .error-title::before {
    content: "Dịch vụ tạm thời không khả dụng";
}

#e503:checked ~ .page .error-description::before {
    content: "Hệ thống hiện đang bận hoặc được bảo trì. Vui lòng thử lại sau.";
}


/* 504 */

#e504:checked ~ .page .error-code::before {
    content: "504";
}

#e504:checked ~ .page .error-title::before {
    content: "Gateway Timeout";
}

#e504:checked ~ .page .error-description::before {
    content: "Máy chủ trung gian đã chờ quá lâu để nhận phản hồi.";
}


/* =========================================================
   GIANT CODE CHANGE
========================================================= */

#e400:checked ~ .page .giant-code::before,
#e400:checked ~ .page .giant-code::after {
    content: "400";
}

#e401:checked ~ .page .giant-code::before,
#e401:checked ~ .page .giant-code::after {
    content: "401";
}

#e402:checked ~ .page .giant-code::before,
#e402:checked ~ .page .giant-code::after {
    content: "402";
}

#e403:checked ~ .page .giant-code::before,
#e403:checked ~ .page .giant-code::after {
    content: "403";
}

#e404:checked ~ .page .giant-code::before,
#e404:checked ~ .page .giant-code::after {
    content: "404";
}

#e405:checked ~ .page .giant-code::before,
#e405:checked ~ .page .giant-code::after {
    content: "405";
}

#e408:checked ~ .page .giant-code::before,
#e408:checked ~ .page .giant-code::after {
    content: "408";
}

#e409:checked ~ .page .giant-code::before,
#e409:checked ~ .page .giant-code::after {
    content: "409";
}

#e410:checked ~ .page .giant-code::before,
#e410:checked ~ .page .giant-code::after {
    content: "410";
}

#e429:checked ~ .page .giant-code::before,
#e429:checked ~ .page .giant-code::after {
    content: "429";
}

#e500:checked ~ .page .giant-code::before,
#e500:checked ~ .page .giant-code::after {
    content: "500";
}

#e501:checked ~ .page .giant-code::before,
#e501:checked ~ .page .giant-code::after {
    content: "501";
}

#e502:checked ~ .page .giant-code::before,
#e502:checked ~ .page .giant-code::after {
    content: "502";
}

#e503:checked ~ .page .giant-code::before,
#e503:checked ~ .page .giant-code::after {
    content: "503";
}

#e504:checked ~ .page .giant-code::before,
#e504:checked ~ .page .giant-code::after {
    content: "504";
}


/* =========================================================
   ANIMATIONS
========================================================= */

@keyframes ambientMove {
    0%,100% {
        transform: translate(0,0) scale(1);
    }

    50% {
        transform: translate(80px,-50px) scale(1.12);
    }
}

@keyframes gridMove {
    from {
        background-position: 0 0, 0 0;
    }

    to {
        background-position: 0 55px, 55px 0;
    }
}

@keyframes starFloat {
    0%,100% {
        transform: translateY(0) scale(.7);
        opacity: .12;
    }

    50% {
        transform: translateY(-25px) scale(1.3);
        opacity: .7;
    }
}

@keyframes logFloat {
    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-12px);
    }
}

@keyframes pulse {
    0%,100% {
        opacity: .45;
        transform: scale(.8);
    }

    50% {
        opacity: 1;
        transform: scale(1.25);
    }
}

@keyframes corePulse {
    0%,100% {
        transform: scale(.9);
        opacity: .6;
    }

    50% {
        transform: scale(1.15);
        opacity: 1;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0);
    }

    to {
        transform: rotate(360deg);
    }
}

@keyframes rotateReverse {
    from {
        transform: rotate(360deg);
    }

    to {
        transform: rotate(0);
    }
}

@keyframes cubeFloat {
    0%,100% {
        transform:
            rotate(45deg)
            translateY(0);
    }

    50% {
        transform:
            rotate(45deg)
            translateY(-8px);
    }
}

@keyframes sparkle {
    0%,100% {
        transform:
            rotate(45deg)
            scale(.4);

        opacity: .15;
    }

    50% {
        transform:
            rotate(45deg)
            scale(1.2);

        opacity: 1;
    }
}

@keyframes glitch {
    0%,86%,100% {
        transform: translate(0);
    }

    88% {
        transform:
            translate(-3px,1px)
            skewX(3deg);
    }

    90% {
        transform:
            translate(3px,-1px)
            skewX(-3deg);
    }

    92% {
        transform:
            translate(-1px);
    }
}

@keyframes blink {
    0%,45% {
        opacity: 1;
    }

    46%,100% {
        opacity: 0;
    }
}

@keyframes lineMove {
    from {
        background-position: 0;
    }

    to {
        background-position: 8px;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 900px) {

    .selector {
        grid-template-columns:
            repeat(5, 1fr);
    }

    .core {
        grid-template-columns: 1fr;

        padding:
            35px 25px 50px;
    }

    .side {
        display: none;
    }

    .center {
        width: 100%;
    }
}

@media (max-width: 600px) {

    body {
        overflow-y: auto;
    }

    .page {
        width: calc(100% - 20px);

        padding-top: 20px;
    }

    .header h1 {
        font-size: 28px;
    }

    .selector {
        grid-template-columns:
            repeat(3, 1fr);
    }

    .selector label {
        min-height: 47px;
    }

    .dashboard {
        min-height: 590px;

        border-radius: 18px;
    }

    .topbar {
        height: 40px;
    }

    .core {
        min-height: 550px;

        padding:
            30px 15px 45px;
    }

    .hologram {
        transform: scale(.85);

        margin-top: -15px;
        margin-bottom: 0;
    }

    .error-code {
        font-size: 48px;
    }

    .error-title {
        font-size: 18px;
    }

    .error-description {
        font-size: 10px;
    }

    .terminal {
        width: 95%;
    }

    .log {
        display: none;
    }

    .connection {
        display: none;
    }
}


/* Runtime error pages show only the active HTTP status. */
.selector{display:none!important}
</style>
</head>


<body>

<div class="noise"></div>


<!-- =======================================================
     BACKGROUND
======================================================= -->

<div class="background">

    <div class="ambient one"></div>
    <div class="ambient two"></div>
    <div class="ambient three"></div>

    <div class="grid"></div>


    <div class="stars">

        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>
        <i class="star"></i>

    </div>


    <div class="log l1">
        ACCESS_DENIED
    </div>

    <div class="log l2">
        ROUTE_NOT_FOUND
    </div>

    <div class="log l3">
        CONNECTION_TIMEOUT
    </div>

    <div class="log l4">
        SERVER_EXCEPTION
    </div>

    <div class="log l5">
        0x00000404
    </div>

    <div class="log l6">
        SYSTEM_RECOVERY
    </div>

</div>


<!-- =======================================================
     STATES
======================================================= -->

<input class="state" type="radio" name="error" id="e400" checked>
<input class="state" type="radio" name="error" id="e401">
<input class="state" type="radio" name="error" id="e402">
<input class="state" type="radio" name="error" id="e403">
<input class="state" type="radio" name="error" id="e404">
<input class="state" type="radio" name="error" id="e405">
<input class="state" type="radio" name="error" id="e408">
<input class="state" type="radio" name="error" id="e409">
<input class="state" type="radio" name="error" id="e410">
<input class="state" type="radio" name="error" id="e429">
<input class="state" type="radio" name="error" id="e500">
<input class="state" type="radio" name="error" id="e501">
<input class="state" type="radio" name="error" id="e502">
<input class="state" type="radio" name="error" id="e503">
<input class="state" type="radio" name="error" id="e504">


<!-- =======================================================
     PAGE
======================================================= -->

<main class="page">


    <!-- HEADER -->

    <header class="header">

        <div class="status-pill">

            <span class="status-light"></span>

            SYSTEM ERROR MONITOR

        </div>

        <h1>
            Something went wrong.
        </h1>

        <p>
            Hệ thống đã ghi nhận trạng thái lỗi hiện tại.
        </p>

    </header>


    <!-- ===================================================
         ERROR SELECTOR
    ==================================================== -->

    <div class="selector">

        <label for="e400">
            <span class="number">400</span>
            <span class="name">BAD REQUEST</span>
        </label>

        <label for="e401">
            <span class="number">401</span>
            <span class="name">UNAUTHORIZED</span>
        </label>

        <label for="e402">
            <span class="number">402</span>
            <span class="name">PAYMENT</span>
        </label>

        <label for="e403">
            <span class="number">403</span>
            <span class="name">FORBIDDEN</span>
        </label>

        <label for="e404">
            <span class="number">404</span>
            <span class="name">NOT FOUND</span>
        </label>

        <label for="e405">
            <span class="number">405</span>
            <span class="name">METHOD</span>
        </label>

        <label for="e408">
            <span class="number">408</span>
            <span class="name">TIMEOUT</span>
        </label>

        <label for="e409">
            <span class="number">409</span>
            <span class="name">CONFLICT</span>
        </label>

        <label for="e410">
            <span class="number">410</span>
            <span class="name">GONE</span>
        </label>

        <label for="e429">
            <span class="number">429</span>
            <span class="name">RATE LIMIT</span>
        </label>

        <label for="e500">
            <span class="number">500</span>
            <span class="name">SERVER</span>
        </label>

        <label for="e501">
            <span class="number">501</span>
            <span class="name">NOT IMPL.</span>
        </label>

        <label for="e502">
            <span class="number">502</span>
            <span class="name">GATEWAY</span>
        </label>

        <label for="e503">
            <span class="number">503</span>
            <span class="name">UNAVAILABLE</span>
        </label>

        <label for="e504">
            <span class="number">504</span>
            <span class="name">GATEWAY TO</span>
        </label>

    </div>


    <!-- ===================================================
         DASHBOARD
    ==================================================== -->

    <section class="dashboard">


        <!-- TOP BAR -->

        <div class="topbar">

            <div class="window-dots">

                <span></span>
                <span></span>
                <span></span>

            </div>

            <div class="window-name">
                ERROR_CORE://SYSTEM_STATUS
            </div>

            <div class="window-status">
                STATUS:
                <span>CRITICAL</span>
            </div>

        </div>


        <!-- =================================================
             CORE
        ================================================== -->

        <div class="core">


            <!-- LEFT -->

            <aside class="side">

                <div class="info-card">

                    <div class="card-label">
                        REQUEST ID
                    </div>

                    <div class="card-value cyan">
                        #7F4A-29C1
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        SERVER
                    </div>

                    <div class="card-value">
                        EDGE-NODE-07
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        RESPONSE
                    </div>

                    <div class="card-value accent">
                        FAILED
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        RETRY
                    </div>

                    <div class="card-value green">
                        AVAILABLE
                    </div>

                </div>

            </aside>


            <!-- CENTER -->

            <div class="center">


                <!-- HOLOGRAM -->

                <div class="hologram">

                    <div class="core-glow"></div>

                    <div class="ring r1"></div>
                    <div class="ring r2"></div>
                    <div class="ring r3"></div>


                    <div class="spark s1"></div>
                    <div class="spark s2"></div>
                    <div class="spark s3"></div>
                    <div class="spark s4"></div>


                    <div class="holo-core">

                        <div class="holo-core-inner">
                            !
                        </div>

                    </div>

                </div>


                <!-- CODE -->

                <div class="error-code">
                    <span></span>
                </div>


                <!-- TITLE -->

                <div class="error-title">
                    <span></span>
                </div>


                <!-- DESCRIPTION -->

                <div class="error-description">
                    <span></span>
                </div>


                <!-- TERMINAL -->

                <div class="terminal">

                    <div class="terminal-line">
                        <span class="prompt">$</span>
                        system diagnostic --check
                        <span class="cursor"></span>
                    </div>

                    <div class="terminal-line">
                        <span class="prompt">></span>
                        <span class="red">
                            [ERROR]
                        </span>
                        request failed
                    </div>

                    <div class="terminal-line">
                        <span class="prompt">></span>
                        <span class="yellow">
                            [WARN]
                        </span>
                        recovery protocol initiated
                    </div>

                    <div class="terminal-line">
                        <span class="prompt">></span>
                        <span class="green">
                            [INFO]
                        </span>
                        diagnostics complete
                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="buttons">

                    <a
                        href="/"
                        class="btn"
                    >
                        ← Trang chủ
                    </a>

                    <a
                        href="javascript:location.reload()"
                        class="btn"
                    >
                        ↻ Thử lại
                    </a>

                </div>

            </div>


            <!-- RIGHT -->

            <aside class="side right">

                <div class="info-card">

                    <div class="card-label">
                        PROTOCOL
                    </div>

                    <div class="card-value">
                        HTTP/2
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        LATENCY
                    </div>

                    <div class="card-value cyan">
                        184 ms
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        SECURITY
                    </div>

                    <div class="card-value green">
                        VERIFIED
                    </div>

                </div>


                <div class="info-card">

                    <div class="card-label">
                        SYSTEM
                    </div>

                    <div class="card-value accent">
                        DEGRADED
                    </div>

                </div>

            </aside>


            <!-- CONNECTION -->

            <div class="connection">

                <span class="connection-dot"></span>

                <span>
                    CLIENT
                </span>

                <span class="connection-line"></span>

                <span>
                    SERVER
                </span>

            </div>


        </div>

    </section>

</main>

</body>
</html>